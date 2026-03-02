<?php

namespace App\AI\Clients;

use App\AI\AiClientInterface;
use App\AI\DTO\AiTicketAnalysisDTO;

class FakeAiClient implements AiClientInterface
{
    public function analyzeTicket(string $title, string $description): AiTicketAnalysisDTO
    {
        // Simulate network latency
        usleep(300_000);

        $text = strtolower($title . ' ' . $description);

        $category = $this->determineCategory($text);
        $sentiment = $this->determineSentiment($text);
        $urgency = $this->determineUrgency($text, $sentiment);
        $reply = $this->generateReply($category, $sentiment);

        return AiTicketAnalysisDTO::fromArray([
            'category' => $category,
            'sentiment' => $sentiment,
            'urgency' => $urgency,
            'reply' => $reply,
        ]);
    }

    private function determineCategory(string $text): string
    {
        $billingKeywords = ['invoice', 'payment', 'charge', 'refund', 'billing'];
        $technicalKeywords = ['error', 'bug', 'crash', 'login', 'password', 'api', '500', 'timeout'];

        if ($this->containsAny($text, $billingKeywords)) {
            return 'Billing';
        }

        if ($this->containsAny($text, $technicalKeywords)) {
            return 'Technical';
        }

        return 'General';
    }

    private function determineSentiment(string $text): string
    {
        $negativeKeywords = ['angry', 'frustrated', 'can\'t', 'cannot', 'not working', 'broken', 'terrible', 'urgent'];
        $positiveKeywords = ['thanks', 'thank you', 'great', 'love'];

        if ($this->containsAny($text, $negativeKeywords)) {
            return 'Negative';
        }

        if ($this->containsAny($text, $positiveKeywords)) {
            return 'Positive';
        }

        return 'Neutral';
    }

    private function determineUrgency(string $text, string $sentiment): string
    {
        $highUrgencyKeywords = ['payment', 'charged', 'refund', 'cannot login', 'data loss', 'security', 'breach'];

        if ($sentiment === 'Negative' && $this->containsAny($text, $highUrgencyKeywords)) {
            return 'High';
        }

        if ($sentiment === 'Positive') {
            return 'Low';
        }

        return 'Medium';
    }

    private function generateReply(string $category, string $sentiment): string
    {
        $reply = "Hello, thank you for reaching out. ";

        if ($sentiment === 'Negative') {
            $reply .= "I sincerely apologize for the inconvenience you are experiencing. ";
        }

        if ($category === 'Billing') {
            $reply .= "Our billing team will review your account details shortly. We will get back to you with an update as soon as possible.";
        } elseif ($category === 'Technical') {
            $reply .= "Our technical support engineers have been notified and are looking into this issue. We will update you with our findings.";
        } else {
            $reply .= "A support representative will assist you with this matter shortly.";
        }

        return $reply;
    }

    /**
     * @param array<int, string> $keywords
     */
    private function containsAny(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
