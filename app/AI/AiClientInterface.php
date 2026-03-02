<?php

namespace App\AI;

use App\AI\DTO\AiTicketAnalysisDTO;

interface AiClientInterface
{
    public function analyzeTicket(string $title, string $description): AiTicketAnalysisDTO;
}