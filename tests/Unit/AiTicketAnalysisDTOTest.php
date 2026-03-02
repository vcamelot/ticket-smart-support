<?php

namespace Tests\Unit;

use App\AI\DTO\AiTicketAnalysisDTO;
use App\AI\Exceptions\AiInvalidResponseException;
use PHPUnit\Framework\TestCase;

class AiTicketAnalysisDTOTest extends TestCase
{
    public function test_it_parses_valid_payload(): void
    {
        $dto = AiTicketAnalysisDTO::fromArray([
            'category' => 'Technical',
            'sentiment' => 'Negative',
            'urgency' => 'High',
            'reply' => 'Please try resetting your password and tell us if the issue persists.',
        ]);

        $this->assertSame('Technical', $dto->category);
        $this->assertSame('Negative', $dto->sentiment);
        $this->assertSame('High', $dto->urgency);
        $this->assertSame(
            'Please try resetting your password and tell us if the issue persists.',
            $dto->reply
        );
    }

    public function test_it_rejects_invalid_category(): void
    {
        $this->expectException(AiInvalidResponseException::class);

        AiTicketAnalysisDTO::fromArray([
            'category' => 'Other',
            'sentiment' => 'Neutral',
            'urgency' => 'Medium',
            'reply' => 'Hello',
        ]);
    }
}