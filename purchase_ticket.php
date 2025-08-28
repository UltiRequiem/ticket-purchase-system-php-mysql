<?php
require_once 'vendor/autoload.php';

use TicketFairy\Config\Database;
use TicketFairy\Services\TicketService;
use TicketFairy\Repositories\EventRepository;
use TicketFairy\Repositories\TicketRepository;
use TicketFairy\Exceptions\InsufficientTicketsException;
use TicketFairy\Exceptions\InvalidInputException;
use TicketFairy\Exceptions\EventNotFoundException;

$database = Database::getInstance();
$db = $database->getConnection();
$eventRepo = new EventRepository($db);
$ticketRepo = new TicketRepository($db);
$ticketService = new TicketService($db, $eventRepo, $ticketRepo);

$error_message = '';
$success_message = '';
$error_type = 'general';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate POST data exists
        if (empty($_POST['event_id']) || empty($_POST['customer_name']) || 
            empty($_POST['customer_email']) || empty($_POST['quantity'])) {
            throw new InvalidInputException("All fields are required");
        }

        $event_id = (int) $_POST['event_id'];
        $customer_name = trim($_POST['customer_name']);
        $customer_email = trim($_POST['customer_email']);
        $quantity = (int) $_POST['quantity'];
        
        $result = $ticketService->purchaseTickets($event_id, $customer_name, $customer_email, $quantity);
        
        $success_message = "🎉 Tickets purchased successfully!<br>" .
                          "<strong>Ticket ID:</strong> {$result['ticket_id']}<br>" .
                          "<strong>Event:</strong> {$result['event_name']}<br>" .
                          "<strong>Quantity:</strong> {$result['quantity']} tickets<br>" .
                          "<strong>Total Amount:</strong> $" . number_format($result['total_amount'], 2);
        
    } catch (InsufficientTicketsException $e) {
        $error_message = "❌ " . $e->getMessage();
        $error_type = 'insufficient';
    } catch (InvalidInputException $e) {
        $error_message = "⚠️ " . $e->getMessage();
        $error_type = 'validation';
    } catch (EventNotFoundException $e) {
        $error_message = "🔍 " . $e->getMessage();
        $error_type = 'not_found';
    } catch (Exception $e) {
        $error_message = "💥 An unexpected error occurred. Please try again later.";
        $error_type = 'system';
        // Log the actual error for debugging
        error_log("Ticket purchase error: " . $e->getMessage());
    }
}

// Get available events for the form
try {
    $stmt = $db->query("SELECT * FROM events ORDER BY event_date");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $events = [];
    if (empty($error_message)) {
        $error_message = "⚠️ Could not load events: " . $e->getMessage();
        $error_type = 'system';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Tickets - TicketFairy</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            max-width: 800px; 
            margin: 0 auto; 
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group { margin-bottom: 20px; }
        label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600;
            color: #333;
        }
        input, select, textarea { 
            width: 100%; 
            padding: 12px; 
            border: 2px solid #e9ecef; 
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        button { 
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white; 
            padding: 15px 30px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        button:hover { 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,123,255,0.3);
        }
        .success { 
            color: #155724;
            padding: 15px; 
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border: 1px solid #c3e6cb; 
            border-radius: 8px; 
            margin-bottom: 20px;
            border-left: 5px solid #28a745;
        }
        .error { 
            color: #721c24;
            padding: 15px; 
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            border: 1px solid #f5c6cb; 
            border-radius: 8px; 
            margin-bottom: 20px;
            border-left: 5px solid #dc3545;
        }
        .error.insufficient {
            border-left-color: #fd7e14;
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            color: #856404;
        }
        .error.validation {
            border-left-color: #6f42c1;
            background: linear-gradient(135deg, #e2d9f3, #d1c4e9);
            color: #4a148c;
        }
        .event-info { 
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 15px; 
            border-radius: 8px; 
            margin-bottom: 15px;
            border-left: 4px solid #007bff;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
        }
        .nav-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background-color 0.3s;
        }
        .nav-link:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎫 Purchase Tickets</h1>
        
        <?php if (!empty($success_message)): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="error <?php echo $error_type; ?>"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (empty($events)): ?>
            <div class="error system">
                ⚠️ No events available. Please check back later or contact support.
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label for="event_id">🎭 Select Event:</label>
                    <select name="event_id" id="event_id" required onchange="updateEventInfo()">
                        <option value="">Choose an event...</option>
                        <?php foreach ($events as $event): ?>
                            <?php
                            // Calculate available tickets for this event
                            $stmt_sold = $db->prepare("SELECT COALESCE(SUM(quantity), 0) as sold FROM tickets WHERE event_id = ?");
                            $stmt_sold->execute([$event['id']]);
                            $sold = $stmt_sold->fetch()['sold'];
                            $available = $event['total_tickets'] - $sold;
                            ?>
                            <option value="<?php echo $event['id']; ?>" 
                                    data-price="<?php echo $event['price']; ?>"
                                    data-total="<?php echo $event['total_tickets']; ?>"
                                    data-available="<?php echo $available; ?>"
                                    data-venue="<?php echo htmlspecialchars($event['venue']); ?>"
                                    data-date="<?php echo $event['event_date']; ?>"
                                    data-description="<?php echo htmlspecialchars($event['description']); ?>">
                                <?php echo htmlspecialchars($event['name']); ?> - $<?php echo number_format($event['price'], 2); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="event-info" class="event-info" style="display: none;">
                    <!-- Event details will be populated by JavaScript -->
                </div>
                
                <div class="form-group">
                    <label for="customer_name">👤 Your Name:</label>
                    <input type="text" name="customer_name" id="customer_name" required 
                           value="<?php echo isset($_POST['customer_name']) ? htmlspecialchars($_POST['customer_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="customer_email">📧 Your Email:</label>
                    <input type="email" name="customer_email" id="customer_email" required
                           value="<?php echo isset($_POST['customer_email']) ? htmlspecialchars($_POST['customer_email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="quantity">🎟️ Number of Tickets:</label>
                    <input type="number" name="quantity" id="quantity" min="1"  required
                           value="<?php echo isset($_POST['quantity']) ? (int)$_POST['quantity'] : ''; ?>">
                </div>
                
                <button type="submit">🛒 Purchase Tickets</button>
            </form>
        <?php endif; ?>
        
        <a href="reports.php" class="nav-link">📊 View Reports</a>
    </div>
    
    <script>
        function updateEventInfo() {
            const select = document.getElementById('event_id');
            const infoDiv = document.getElementById('event-info');
            const option = select.options[select.selectedIndex];
            
            if (option.value) {
                const price = option.getAttribute('data-price');
                const total = option.getAttribute('data-total');
                const available = option.getAttribute('data-available');
                const venue = option.getAttribute('data-venue');
                const date = option.getAttribute('data-date');
                const description = option.getAttribute('data-description');
                
                infoDiv.innerHTML = `
                    <strong>📍 Event Details:</strong><br>
                    <strong>Description:</strong> ${description}<br>
                    <strong>Venue:</strong> ${venue}<br>
                    <strong>Date:</strong> ${new Date(date).toLocaleString()}<br>
                    <strong>Price per ticket:</strong> $${parseFloat(price).toFixed(2)}<br>
                    <strong>Total tickets:</strong> ${total}<br>
                    <strong>Available tickets:</strong> ${available}
                `;
                infoDiv.style.display = 'block';
            } else {
                infoDiv.style.display = 'none';
            }
        }
        
        // Preserve form state after error
        <?php if (isset($_POST['event_id']) && !empty($error_message)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('event_id').value = '<?php echo (int)$_POST['event_id']; ?>';
                updateEventInfo();
            });
        <?php endif; ?>
    </script>
</body>
</html>