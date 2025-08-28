<?php
require_once 'vendor/autoload.php';

use TicketFairy\Config\Database;
use TicketFairy\Services\TicketService;
use TicketFairy\Repositories\EventRepository;
use TicketFairy\Repositories\TicketRepository;
use TicketFairy\Exceptions\InsufficientTicketsException;
use TicketFairy\Exceptions\InvalidInputException;

$database = Database::getInstance();
$db = $database->getConnection();
$eventRepo = new EventRepository($db);
$ticketRepo = new TicketRepository($db);
$ticketService = new TicketService($db, $eventRepo, $ticketRepo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $event_id = (int) $_POST['event_id'];
        $customer_name = trim($_POST['customer_name']);
        $customer_email = trim($_POST['customer_email']);
        $quantity = (int) $_POST['quantity'];
        
        $result = $ticketService->purchaseTickets($event_id, $customer_name, $customer_email, $quantity);
        
        $success_message = "Tickets purchased successfully! Ticket ID: {$result['ticket_id']}";
    } catch (InsufficientTicketsException $e) {
        $error_message = $e->getMessage();
    } catch (InvalidInputException $e) {
        $error_message = $e->getMessage();
    } catch (Exception $e) {
        $error_message = "An error occurred: " . $e->getMessage();
    }
}

// Get available events for the form
try {
    $stmt = $db->query("SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $events = [];
    $error_message = "Could not load events: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Tickets - TicketFairy</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .success { color: green; padding: 10px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 20px; }
        .error { color: red; padding: 10px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 20px; }
        .event-info { background-color: #f8f9fa; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>Purchase Tickets</h1>
    
    <?php if (isset($success_message)): ?>
        <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="event_id">Select Event:</label>
            <select name="event_id" id="event_id" required onchange="updateEventInfo()">
                <option value="">Choose an event...</option>
                <?php foreach ($events as $event): ?>
                    <option value="<?php echo $event['id']; ?>" 
                            data-price="<?php echo $event['price']; ?>"
                            data-total="<?php echo $event['total_tickets']; ?>"
                            data-venue="<?php echo htmlspecialchars($event['venue']); ?>"
                            data-date="<?php echo $event['event_date']; ?>">
                        <?php echo htmlspecialchars($event['name']); ?> - $<?php echo number_format($event['price'], 2); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div id="event-info" class="event-info" style="display: none;">
            <!-- Event details will be populated by JavaScript -->
        </div>
        
        <div class="form-group">
            <label for="customer_name">Your Name:</label>
            <input type="text" name="customer_name" id="customer_name" required>
        </div>
        
        <div class="form-group">
            <label for="customer_email">Your Email:</label>
            <input type="email" name="customer_email" id="customer_email" required>
        </div>
        
        <div class="form-group">
            <label for="quantity">Number of Tickets:</label>
            <input type="number" name="quantity" id="quantity" min="1" required>
        </div>
        
        <button type="submit">Purchase Tickets</button>
    </form>
    
    <p><a href="reports.php">View Reports</a></p>
    
    <script>
        function updateEventInfo() {
            const select = document.getElementById('event_id');
            const infoDiv = document.getElementById('event-info');
            const option = select.options[select.selectedIndex];
            
            if (option.value) {
                const price = option.getAttribute('data-price');
                const total = option.getAttribute('data-total');
                const venue = option.getAttribute('data-venue');
                const date = option.getAttribute('data-date');
                
                infoDiv.innerHTML = `
                    <strong>Event Details:</strong><br>
                    Venue: ${venue}<br>
                    Date: ${date}<br>
                    Price per ticket: $${parseFloat(price).toFixed(2)}<br>
                    Total tickets available: ${total}
                `;
                infoDiv.style.display = 'block';
            } else {
                infoDiv.style.display = 'none';
            }
        }
    </script>
</body>
</html>