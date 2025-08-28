<?php
namespace TicketFairy\Repositories;

use TicketFairy\Contracts\EventRepositoryInterface;
use TicketFairy\Models\Event;
use PDO;

class EventRepository implements EventRepositoryInterface {
    private $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    public function findByIdWithLock(int $id): ?Event {
        $stmt = $this->db->prepare("SELECT * FROM events WHERE id = ? FOR UPDATE");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? new Event($data) : null;
    }
    
    public function findById(int $id): ?Event {
        $stmt = $this->db->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? new Event($data) : null;
    }
}