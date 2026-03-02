<?php

namespace App\AI\Clients;

use App\AI\AiClientInterface;
use App\AI\DTO\AiTicketAnalysisDTO;
use App\AI\Exceptions\AiInvalidResponseException;
use App\AI\Prompts\TicketAnalysisPrompt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiAiClient implements AiClientInterface
{
    private string $apiKey;
    private string $model;
    private string $baseUrl;

    public function __construct(string $apiKey, string $model, ?string $baseUrl = null)
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->baseUrl = rtrim($baseUrl ?? 'https://generativelanguage.googleapis.com/v1beta/models', '/');
    }

    public function analyzeTicket(string $title, string $description): AiTicketAnalysisDTO
    {
        if (trim($this->apiKey) === '') {
            throw new AiInvalidResponseException('GEMINI_API_KEY is not set');
        }

        $url = "{$this->baseUrl}/{$this->model}:generateContent";

        $response = Http::timeout(30)
            ->withQueryParameters(['key' => $this->apiKey])
            ->post($url, $payload);

        $systemPrompt = TicketAnalysisPrompt::systemPrompt();
        $userPrompt = TicketAnalysisPrompt::userPrompt($title, $description);

        $payload = [
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemPrompt]
                ]
            ],
            'contents' => [
                [
                    'parts' => [
                        ['text' => $userPrompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'responseMimeType' => 'application/json',
            ],
        ];

        try {
            $response = Http::timeout(30)->post($url, $payload);

            if (!$response->successful()) {
                throw new \Exception('HTTP request to Gemini API failed: ' . $response->body());
            }

            $jsonResponse = $response->json();
            
            if (!isset($jsonResponse['candidates'][0]['content']['parts'][0]['text'])) {
                throw new \Exception('Unexpected Gemini API response structure.');
            }

            $rawText = $jsonResponse['candidates'][0]['content']['parts'][0]['text'];
            
            return $this->parseAndValidateResponse($rawText);
            
        } catch (\Exception $e) {
            Log::warning('Gemini AI analysis failed.', [
                'ticket_id' => $ticketId ?? null,
                'title_hash' => substr(sha1($title), 0, 12),
                'error' => $e->getMessage()
            ]);
            
            throw new AiInvalidResponseException('Failed to analyze ticket with Gemini: ' . $e->getMessage());
        }
    }

    private function parseAndValidateResponse(string $rawText): AiTicketAnalysisDTO
    {
        $text = trim($rawText);

        // Attempt to extract JSON if there's markdown or extra text around it
        $firstBrace = strpos($text, '{');
        $lastBrace = strrpos($text, '}');

        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            $text = substr($text, $firstBrace, $lastBrace - $firstBrace + 1);
        }

        $decoded = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new AiInvalidResponseException('Gemini returned invalid JSON: ' . json_last_error_msg());
        }

        if (!is_array($decoded)) {
            throw new AiInvalidResponseException('Gemini returned JSON that is not an object.');
        }

        try {
            return AiTicketAnalysisDTO::fromArray($decoded);
        } catch (\Exception $e) {
            // Re-throw validation errors as AiInvalidResponseException
            throw new AiInvalidResponseException($e->getMessage());
        }
    }
}
