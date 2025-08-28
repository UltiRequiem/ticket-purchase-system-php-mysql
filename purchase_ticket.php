<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

require_once 'vendor/autoload.php';

use TicketFairy\Config\Database;
use TicketFairy\Services\TicketService;
use TicketFairy\Repositories\EventRepository;
use TicketFairy\Repositories\TicketRepository;
use TicketFairy\Exceptions\InsufficientTicketsException;
use TicketFairy\Exceptions\InvalidInputException;
use TicketFairy\Exceptions\EventNotFoundException;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    try {
        $database = Database::getInstance();
        $db = $database->getConnection();
        $eventRepo = new EventRepository($db);
        $ticketRepo = new TicketRepository($db);
        $ticketService = new TicketService($db, $eventRepo, $ticketRepo);
        
        if (empty($_POST['event_id']) || empty($_POST['customer_name']) || 
            empty($_POST['customer_email']) || empty($_POST['quantity'])) {
            throw new InvalidInputException("All fields are required");
        }

        $event_id = (int) $_POST['event_id'];
        $customer_name = trim($_POST['customer_name']);
        $customer_email = trim($_POST['customer_email']);
        $quantity = (int) $_POST['quantity'];
        
        $result = $ticketService->purchaseTickets($event_id, $customer_name, $customer_email, $quantity);
        
        echo json_encode([
            'success' => true,
            'message' => "🎉 Tickets purchased successfully!<br>" .
                        "<strong>Ticket ID:</strong> {$result['ticket_id']}<br>" .
                        "<strong>Event:</strong> {$result['event_name']}<br>" .
                        "<strong>Quantity:</strong> {$result['quantity']} tickets<br>" .
                        "<strong>Total Amount:</strong> $" . number_format($result['total_amount'], 2)
        ]);
        
    } catch (InsufficientTicketsException $e) {
        echo json_encode([
            'success' => false,
            'message' => "❌ " . $e->getMessage(),
            'type' => 'insufficient'
        ]);
    } catch (InvalidInputException $e) {
        echo json_encode([
            'success' => false,
            'message' => "⚠️ " . $e->getMessage(),
            'type' => 'validation'
        ]);
    } catch (EventNotFoundException $e) {
        echo json_encode([
            'success' => false,
            'message' => "🔍 " . $e->getMessage(),
            'type' => 'not_found'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => "💥 An unexpected error occurred. Please try again later.",
            'type' => 'system'
        ]);
        error_log("Ticket purchase error: " . $e->getMessage());
    } catch (Error $e) {
        echo json_encode([
            'success' => false,
            'message' => "💥 System error occurred. Please check your configuration.",
            'type' => 'system'
        ]);
        error_log("Fatal error in ticket purchase: " . $e->getMessage());
    }
    exit;
}

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    $stmt = $db->query("SELECT * FROM events ORDER BY event_date");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $events = [];
    error_log("Error loading events: " . $e->getMessage());
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
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #007bff;
        }
        button { 
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white; 
            padding: 15px 30px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 18px;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
        }
        button:hover { 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,123,255,0.3);
        }
        button:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .success, .error { 
            padding: 15px; 
            margin: 20px 0; 
            border-radius: 6px; 
            font-weight: 500;
        }
        .success { 
            background-color: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb;
        }
        .error { 
            background-color: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb;
        }
        .error.insufficient { background-color: #fff3cd; color: #856404; border-color: #ffeaa7; }
        .error.validation { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .error.not_found { background-color: #d1ecf1; color: #0c5460; border-color: #bee5eb; }
        .error.system { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .event-info {
            background-color: #e7f3ff;
            border: 1px solid #b8daff;
            border-radius: 6px;
            padding: 15px;
            margin-top: 10px;
        }
        h1 { 
            color: #333; 
            text-align: center; 
            margin-bottom: 30px;
            font-size: 2.5em;
        }
        .loading {
            display: none;
            text-align: center;
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎫 Purchase Tickets</h1>
        
        <div id="message-container"></div>
        
        <?php if (empty($events)): ?>
            <div class="error system">
                ⚠️ No events available. Please check back later or contact support.
            </div>
        <?php else: ?>
            <form id="ticket-form">
                <div class="form-group">
                    <label for="event_id">🎭 Select Event:</label>
                    <select name="event_id" id="event_id" required onchange="updateEventInfo()">
                        <option value="">Choose an event...</option>
                        <?php foreach ($events as $event): ?>
                            <?php
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
                    <label for="customer_name">👤 Full Name:</label>
                    <input type="text" name="customer_name" id="customer_name" required 
                           placeholder="Enter your full name">
                </div>
                
                <div class="form-group">
                    <label for="customer_email">📧 Email Address:</label>
                    <input type="email" name="customer_email" id="customer_email" required 
                           placeholder="Enter your email address">
                </div>
                
                <div class="form-group">
                    <label for="quantity">🎟️ Number of Tickets:</label>
                    <input type="number" name="quantity" id="quantity" min="1" required 
                           placeholder="How many tickets?">
                </div>
                
                <div class="loading" id="loading">
                    🔄 Processing your purchase...
                </div>
                
                <button type="submit" id="submit-btn">🛒 Purchase Tickets</button>
            </form>
        <?php endif; ?>
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
        
        // Handle form submission with AJAX
        document.getElementById('ticket-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('ajax', '1');
            
            const submitBtn = document.getElementById('submit-btn');
            const loading = document.getElementById('loading');
            const messageContainer = document.getElementById('message-container');
            
            submitBtn.disabled = true;
            loading.style.display = 'block';
            messageContainer.innerHTML = '';
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageContainer.innerHTML = `<div class="success">${data.message}</div>`;
                    // Reset form on success
                    document.getElementById('ticket-form').reset();
                    document.getElementById('event-info').style.display = 'none';
                } else {
                    messageContainer.innerHTML = `<div class="error ${data.type || 'system'}">${data.message}</div>`;
                }
            })
            .catch(error => {
                messageContainer.innerHTML = `<div class="error system">💥 Network error occurred. Please check your connection and try again.</div>`;
                console.error('Error:', error);
            })
            .finally(() => {
                // Hide loading state
                submitBtn.disabled = false;
                loading.style.display = 'none';
            });
        });
    </script>
</body>
</html>