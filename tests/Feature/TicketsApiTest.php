<?php

namespace Tests\Feature;

use App\AI\AiClientInterface;
use App\AI\Clients\FakeAiClient;
use App\Jobs\EnrichTicketWithAi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class TicketsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Force the fake client for testing
        config(['ai.provider' => 'fake']);
        $this->app->bind(AiClientInterface::class, FakeAiClient::class);
    }

    public function test_can_create_and_enrich_ticket_async()
    {
        // Intercept queued jobs
        Bus::fake();

        $payload = [
            'title' => 'Cannot login',
            'description' => 'I forgot my password and cannot reset it. I am frustrated.',
        ];

        // 1) POST to create ticket
        $response = $this->postJson('/api/tickets', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id', 'title', 'description', 'status', 
                    'category', 'sentiment', 'urgency', 'suggested_reply',
                ]
            ]);

        $ticketId = $response->json('data.id');

        // 2) Assert the job was dispatched
        Bus::assertDispatched(EnrichTicketWithAi::class, function ($job) use ($ticketId) {
            return $job->ticketId === $ticketId;
        });

        // 3) Verify ticket initially has null AI fields
        $this->getJson('/api/tickets/' . $ticketId)
            ->assertStatus(200)
            ->assertJsonPath('data.category', null)
            ->assertJsonPath('data.sentiment', null)
            ->assertJsonPath('data.urgency', null)
            ->assertJsonPath('data.suggested_reply', null);

        // 4) Execute the job manually to simulate queue worker
        $job = new EnrichTicketWithAi($ticketId);
        $job->handle(app(AiClientInterface::class));

        // 5) Verify ticket has been enriched (using FakeAiClient heuristics)
        $this->getJson('/api/tickets/' . $ticketId)
            ->assertStatus(200)
            ->assertJsonPath('data.category', 'Technical')
            ->assertJsonPath('data.sentiment', 'Negative')
            ->assertJsonPath('data.urgency', 'High');
    }
}
