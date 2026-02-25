<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'Open';
    case Resolved = 'Resolved';
}
