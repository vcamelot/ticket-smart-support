<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Ticket;
use App\AI\AiClientInterface;
use App\AI\Exceptions\AiInvalidResponseException;
use Illuminate\Support\Facades\Log;

class EnrichTicketWithAi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [1, 5, 15];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $ticketId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AiClientInterface $aiClient): void
    {
        $ticket = Ticket::find($this->ticketId);

        if (!$ticket) {
            return;
        }

        try {
            $analysis = $aiClient->analyzeTicket($ticket->title, $ticket->description);

            $ticket->update([
                'category' => $analysis->category,
                'sentiment' => $analysis->sentiment,
                'urgency' => $analysis->urgency,
                'suggested_reply' => $analysis->reply,
            ]);
        } catch (AiInvalidResponseException $e) {
            Log::warning('AI ticket enrichment failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
