<?php
namespace TicketFairy\Models;

class Ticket {
    private $id;
    private $eventId;
    private $customerName;
    private $customerEmail;
    private $quantity;
    private $totalAmount;
    private $purchaseDate;
    
    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->eventId = $data['event_id'] ?? null;
        $this->customerName = $data['customer_name'] ?? '';
        $this->customerEmail = $data['customer_email'] ?? '';
        $this->quantity = $data['quantity'] ?? 0;
        $this->totalAmount = $data['total_amount'] ?? 0.0;
        $this->purchaseDate = $data['purchase_date'] ?? null;
    }
    
    public function getId(): ?int {
        return $this->id;
    }
    
    public function getEventId(): ?int {
        return $this->eventId;
    }
    
    public function getCustomerName(): string {
        return $this->customerName;
    }
    
    public function getCustomerEmail(): string {
        return $this->customerEmail;
    }
    
    public function getQuantity(): int {
        return $this->quantity;
    }
    
    public function getTotalAmount(): float {
        return $this->totalAmount;
    }
    
    public function getPurchaseDate(): ?string {
        return $this->purchaseDate;
    }
    
    // Setters
    public function setId(int $id): void {
        $this->id = $id;
    }
    
    public function setEventId(int $eventId): void {
        $this->eventId = $eventId;
    }
    
    public function setCustomerName(string $customerName): void {
        $this->customerName = $customerName;
    }
    
    public function setCustomerEmail(string $customerEmail): void {
        $this->customerEmail = $customerEmail;
    }
    
    public function setQuantity(int $quantity): void {
        $this->quantity = $quantity;
    }
    
    public function setTotalAmount(float $totalAmount): void {
        $this->totalAmount = $totalAmount;
    }
}