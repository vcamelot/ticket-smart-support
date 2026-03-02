<?php

namespace App\AI\DTO;

use App\AI\Exceptions\AiInvalidResponseException;

final readonly class AiTicketAnalysisDTO
{
    public const CATEGORIES = ['Technical', 'Billing', 'General'];
    public const SENTIMENTS = ['Positive', 'Neutral', 'Negative'];
    public const URGENCIES = ['Low', 'Medium', 'High'];

    public function __construct(
        public string $category,
        public string $sentiment,
        public string $urgency,
        public string $reply,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        // Must contain exactly these 4 keys (no more, no less)
        $expectedKeys = ['category', 'sentiment', 'urgency', 'reply'];
        $keys = array_keys($data);
        sort($expectedKeys);
        sort($keys);

        if ($keys !== $expectedKeys) {
            throw new AiInvalidResponseException(
                'AI response must be a JSON object with exactly keys: category, sentiment, urgency, reply.'
            );
        }

        foreach ($expectedKeys as $key) {
            if (!is_string($data[$key])) {
                throw new AiInvalidResponseException("AI response field '{$key}' must be a string.");
            }
        }

        $category = trim($data['category']);
        $sentiment = trim($data['sentiment']);
        $urgency = trim($data['urgency']);
        $reply = trim($data['reply']);

        if (!in_array($category, self::CATEGORIES, true)) {
            throw new AiInvalidResponseException("Invalid category '{$category}'.");
        }
        if (!in_array($sentiment, self::SENTIMENTS, true)) {
            throw new AiInvalidResponseException("Invalid sentiment '{$sentiment}'.");
        }
        if (!in_array($urgency, self::URGENCIES, true)) {
            throw new AiInvalidResponseException("Invalid urgency '{$urgency}'.");
        }
        if ($reply === '') {
            throw new AiInvalidResponseException('Reply must be a non-empty string.');
        }

        return new self(
            category: $category,
            sentiment: $sentiment,
            urgency: $urgency,
            reply: $reply
        );
    }

    public function toArray(): array
    {
        return [
            'category' => $this->category,
            'sentiment' => $this->sentiment,
            'urgency' => $this->urgency,
            'reply' => $this->reply,
        ];
    }
}