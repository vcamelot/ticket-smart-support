<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\TicketStatus;

class Ticket extends Model
{
    /** @use HasFactory<\Database\Factories\TicketFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'category',
        'sentiment',
        'urgency',
        'suggested_reply',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
        ];
    }
}
