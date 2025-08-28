<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Insert sample events
    $events = [
        [
            'name' => 'Summer Music Festival',
            'description' => 'A three-day outdoor music festival featuring top artists',
            'event_date' => '2024-07-15 18:00:00',
            'venue' => 'Central Park Amphitheater',
            'total_tickets' => 1000,
            'price' => 75.00
        ],
        [
            'name' => 'Tech Conference 2024',
            'description' => 'Annual technology conference with industry leaders',
            'event_date' => '2024-08-20 09:00:00',
            'venue' => 'Convention Center Hall A',
            'total_tickets' => 500,
            'price' => 150.00
        ],
        [
            'name' => 'Comedy Night',
            'description' => 'Stand-up comedy show with local comedians',
            'event_date' => '2024-06-30 20:00:00',
            'venue' => 'Downtown Comedy Club',
            'total_tickets' => 200,
            'price' => 25.00
        ]
    ];
    
    $stmt = $db->prepare(
        "INSERT INTO events (name, description, event_date, venue, total_tickets, price) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    
    foreach ($events as $event) {
        $stmt->execute([
            $event['name'],
            $event['description'],
            $event['event_date'],
            $event['venue'],
            $event['total_tickets'],
            $event['price']
        ]);
    }
    
    echo "Sample events inserted successfully!\n";
    
    // Insert some sample ticket purchases
    $purchases = [
        [1, 'John Doe', 'john@example.com', 2],
        [1, 'Jane Smith', 'jane@example.com', 4],
        [2, 'Bob Johnson', 'bob@example.com', 1],
        [3, 'Alice Brown', 'alice@example.com', 3]
    ];
    
    foreach ($purchases as $purchase) {
        // Get event price
        $eventStmt = $db->prepare("SELECT price FROM events WHERE id = ?");
        $eventStmt->execute([$purchase[0]]);
        $event = $eventStmt->fetch();
        
        if ($event) {
            $totalAmount = $purchase[3] * $event['price'];
            
            $ticketStmt = $db->prepare(
                "INSERT INTO tickets (event_id, customer_name, customer_email, quantity, total_amount) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            $ticketStmt->execute([
                $purchase[0],
                $purchase[1],
                $purchase[2],
                $purchase[3],
                $totalAmount
            ]);
        }
    }
    
    echo "Sample ticket purchases inserted successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>