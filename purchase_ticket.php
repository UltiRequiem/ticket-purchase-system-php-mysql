<?php
require_once 'config/database.php';

class TicketPurchase {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function purchaseTickets($event_id, $customer_name, $customer_email, $quantity) {
        try {
            $this->db->beginTransaction();
            
            // Get event details with row lock to prevent race conditions
            $stmt = $this->db->prepare("SELECT * FROM events WHERE id = ? FOR UPDATE");
            $stmt->execute([$event_id]);
            $event = $stmt->fetch();
            
            if (!$event) {
                throw new Exception("Event not found");
            }
            
            // Calculate total tickets sold for this event
            $stmt = $this->db->prepare("SELECT COALESCE(SUM(quantity), 0) as sold FROM tickets WHERE event_id = ?");
            $stmt->execute([$event_id]);
            $sold = $stmt->fetch()['sold'];
            
            // Check if enough tickets are available
            $available = $event['total_tickets'] - $sold;
            if ($quantity > $available) {
                throw new Exception("Not enough tickets available. Only {$available} tickets left.");
            }
            
            if ($quantity <= 0) {
                throw new Exception("Quantity must be greater than 0");
            }
            
            if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email address");
            }
            
            // Calculate total amount
            $total_amount = $quantity * $event['price'];
            
            // Insert ticket purchase
            $stmt = $this->db->prepare(
                "INSERT INTO tickets (event_id, customer_name, customer_email, quantity, total_amount) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$event_id, $customer_name, $customer_email, $quantity, $total_amount]);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Tickets purchased successfully',
                'ticket_id' => $this->db->lastInsertId(),
                'total_amount' => $total_amount
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function getAvailableTickets($event_id) {
        try {
            $stmt = $this->db->prepare(
                "SELECT e.total_tickets, COALESCE(SUM(t.quantity), 0) as sold,
                        (e.total_tickets - COALESCE(SUM(t.quantity), 0)) as available
                 FROM events e
                 LEFT JOIN tickets t ON e.id = t.event_id
                 WHERE e.id = ?
                 GROUP BY e.id"
            );
            $stmt->execute([$event_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return false;
        }
    }
}

// Handle POST request for ticket purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $event_id = $input['event_id'] ?? null;
    $customer_name = $input['customer_name'] ?? '';
    $customer_email = $input['customer_email'] ?? '';
    $quantity = $input['quantity'] ?? 0;
    
    $ticketPurchase = new TicketPurchase();
    $result = $ticketPurchase->purchaseTickets($event_id, $customer_name, $customer_email, $quantity);
    
    echo json_encode($result);
    exit;
}

// Handle GET request for available tickets
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['event_id'])) {
    header('Content-Type: application/json');
    
    $ticketPurchase = new TicketPurchase();
    $result = $ticketPurchase->getAvailableTickets($_GET['event_id']);
    
    echo json_encode($result);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ticket Purchase</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #005a87; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <h1>Purchase Tickets</h1>
    
    <form id="purchaseForm">
        <div class="form-group">
            <label for="event_id">Event ID:</label>
            <input type="number" id="event_id" name="event_id" required>
        </div>
        
        <div class="form-group">
            <label for="customer_name">Customer Name:</label>
            <input type="text" id="customer_name" name="customer_name" required>
        </div>
        
        <div class="form-group">
            <label for="customer_email">Email:</label>
            <input type="email" id="customer_email" name="customer_email" required>
        </div>
        
        <div class="form-group">
            <label for="quantity">Quantity:</label>
            <input type="number" id="quantity" name="quantity" min="1" required>
        </div>
        
        <button type="submit">Purchase Tickets</button>
        <button type="button" onclick="checkAvailability()">Check Availability</button>
    </form>
    
    <div id="message"></div>
    
    <script>
        document.getElementById('purchaseForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                event_id: document.getElementById('event_id').value,
                customer_name: document.getElementById('customer_name').value,
                customer_email: document.getElementById('customer_email').value,
                quantity: parseInt(document.getElementById('quantity').value)
            };
            
            fetch('purchase_ticket.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                const messageDiv = document.getElementById('message');
                messageDiv.className = 'message ' + (data.success ? 'success' : 'error');
                messageDiv.innerHTML = data.message;
                if (data.success) {
                    messageDiv.innerHTML += `<br>Ticket ID: ${data.ticket_id}<br>Total Amount: $${data.total_amount}`;
                    document.getElementById('purchaseForm').reset();
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
        
        function checkAvailability() {
            const eventId = document.getElementById('event_id').value;
            if (!eventId) {
                alert('Please enter an Event ID first');
                return;
            }
            
            fetch(`purchase_ticket.php?event_id=${eventId}`)
            .then(response => response.json())
            .then(data => {
                if (data) {
                    const messageDiv = document.getElementById('message');
                    messageDiv.className = 'message success';
                    messageDiv.innerHTML = `Available tickets: ${data.available} / ${data.total_tickets}`;
                } else {
                    const messageDiv = document.getElementById('message');
                    messageDiv.className = 'message error';
                    messageDiv.innerHTML = 'Event not found';
                }
            });
        }
    </script>
</body>
</html>