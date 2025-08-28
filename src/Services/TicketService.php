<?php
namespace TicketFairy\Services;

use TicketFairy\Contracts\EventRepositoryInterface;
use TicketFairy\Contracts\TicketRepositoryInterface;
use TicketFairy\Models\Ticket;
use TicketFairy\Exceptions\InsufficientTicketsException;
use TicketFairy\Exceptions\InvalidInputException;
use PDO;

class TicketService {
    private $db;
    private $eventRepo;
    private $ticketRepo;
    
    public function __construct(PDO $db, EventRepositoryInterface $eventRepo, TicketRepositoryInterface $ticketRepo) {
        $this->db = $db;
        $this->eventRepo = $eventRepo;
        $this->ticketRepo = $ticketRepo;
    }
    
    public function purchaseTickets(int $eventId, string $customerName, string $customerEmail, int $quantity): array {
        $this->validateInput($customerEmail, $quantity);
        
        try {
            $this->db->beginTransaction();
            
            $event = $this->eventRepo->findByIdWithLock($eventId);
            if (!$event) {
                throw new InvalidInputException("Event not found");
            }
            
            $available = $event->getAvailableTickets($this->db);
            if ($quantity > $available) {
                throw new InsufficientTicketsException(
                    "Not enough tickets available. Only {$available} tickets left."
                );
            }
            
            $ticket = new Ticket([
                'event_id' => $eventId,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'quantity' => $quantity,
                'total_amount' => $quantity * $event->getPrice()
            ]);
            
            $ticketId = $this->ticketRepo->save($ticket);
            $this->db->commit();
            
            return [
                'ticket_id' => $ticketId,
                'event_name' => $event->getName(),
                'quantity' => $quantity,
                'total_amount' => $ticket->getTotalAmount()
            ];
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    private function validateInput(string $email, int $quantity): void {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidInputException("Invalid email address");
        }
        
        if ($quantity <= 0) {
            throw new InvalidInputException("Quantity must be greater than 0");
        }
    }
}