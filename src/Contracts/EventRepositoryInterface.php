<?php
namespace TicketFairy\Contracts;

use TicketFairy\Models\Event;

interface EventRepositoryInterface {
    public function findByIdWithLock(int $id): ?Event;
    public function findById(int $id): ?Event;
}