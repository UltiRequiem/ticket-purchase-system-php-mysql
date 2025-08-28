<?php
namespace TicketFairy\Models;

class Event {
    private $id;
    private $name;
    private $description;
    private $eventDate;
    private $venue;
    private $totalTickets;
    private $price;
    
    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->eventDate = $data['event_date'] ?? null;
        $this->venue = $data['venue'] ?? '';
        $this->totalTickets = $data['total_tickets'] ?? 0;
        $this->price = $data['price'] ?? 0.0;
    }
    
    public function getDescription(): string { return $this->description; }
    public function getEventDate(): ?string { return $this->eventDate; }
    public function getVenue(): string { return $this->venue; }
    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getTotalTickets(): int { return $this->totalTickets; }
    public function getPrice(): float { return $this->price; }
    
    public function getAvailableTickets(\PDO $db): int {
        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(quantity), 0) as sold FROM tickets WHERE event_id = ?"
        );
        $stmt->execute([$this->id]);
        $sold = $stmt->fetch()['sold'];
        return $this->totalTickets - $sold;
    }
}