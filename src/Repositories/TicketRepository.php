<?php
namespace TicketFairy\Repositories;

use TicketFairy\Contracts\TicketRepositoryInterface;
use TicketFairy\Models\Ticket;
use PDO;

class TicketRepository implements TicketRepositoryInterface {
    private $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    public function save(Ticket $ticket): int {
        $stmt = $this->db->prepare(
            "INSERT INTO tickets (event_id, customer_name, customer_email, quantity, total_amount, created_at) 
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        
        $stmt->execute([
            $ticket->getEventId(),
            $ticket->getCustomerName(),
            $ticket->getCustomerEmail(),
            $ticket->getQuantity(),
            $ticket->getTotalAmount()
        ]);
        
        return (int) $this->db->lastInsertId();
    }
    
    public function findByEventId(int $eventId): array {
        $stmt = $this->db->prepare("SELECT * FROM tickets WHERE event_id = ?");
        $stmt->execute([$eventId]);
        
        $tickets = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tickets[] = new Ticket($data);
        }
        
        return $tickets;
    }
    
    public function getTotalSoldByEvent(): array {
        $stmt = $this->db->query(
            "SELECT e.name, e.id, COALESCE(SUM(t.quantity), 0) as total_sold 
             FROM events e 
             LEFT JOIN tickets t ON e.id = t.event_id 
             GROUP BY e.id, e.name"
        );
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}