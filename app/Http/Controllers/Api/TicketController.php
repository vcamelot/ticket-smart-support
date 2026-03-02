<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Enums\TicketStatus;
use App\AI\AiClientInterface;

class TicketController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTicketRequest $request, AiClientInterface $aiClient)
    {
        $ticket = Ticket::create([
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
            'status' => TicketStatus::Open,
        ]);

        $analysis = $aiClient->analyzeTicket($ticket->title, $ticket->description);

        $ticket->update([
            'category' => $analysis->category,
            'sentiment' => $analysis->sentiment,
            'urgency' => $analysis->urgency,
            'suggested_reply' => $analysis->reply,
        ]);

        return (new TicketResource($ticket->fresh()))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ticket $ticket)
    {
        return new TicketResource($ticket);
    }
}
