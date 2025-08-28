<?php
namespace TicketFairy\Contracts;

use TicketFairy\Models\Ticket;

interface TicketRepositoryInterface {
    public function save(Ticket $ticket): int;
    public function findByEventId(int $eventId): array;
    public function getTotalSoldByEvent(): array;
}