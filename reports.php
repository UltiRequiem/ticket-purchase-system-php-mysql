<?php
require_once 'config/database.php';

class TicketReports {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function getTotalTicketsSoldPerEvent() {
        try {
            $stmt = $this->db->prepare(
                "SELECT 
                    e.id,
                    e.name,
                    e.event_date,
                    e.venue,
                    e.total_tickets,
                    e.price,
                    COALESCE(SUM(t.quantity), 0) as tickets_sold,
                    (e.total_tickets - COALESCE(SUM(t.quantity), 0)) as tickets_remaining,
                    COALESCE(SUM(t.total_amount), 0) as total_revenue
                 FROM events e
                 LEFT JOIN tickets t ON e.id = t.event_id
                 GROUP BY e.id, e.name, e.event_date, e.venue, e.total_tickets, e.price
                 ORDER BY e.event_date DESC"
            );
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getEventDetails($event_id) {
        try {
            $stmt = $this->db->prepare(
                "SELECT 
                    e.*,
                    COALESCE(SUM(t.quantity), 0) as tickets_sold,
                    COALESCE(SUM(t.total_amount), 0) as total_revenue
                 FROM events e
                 LEFT JOIN tickets t ON e.id = t.event_id
                 WHERE e.id = ?
                 GROUP BY e.id"
            );
            $stmt->execute([$event_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
}

$reports = new TicketReports();
$events = $reports->getTotalTicketsSoldPerEvent();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ticket Sales Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:hover { background-color: #f5f5f5; }
        .summary { background: #e7f3ff; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .sold-out { background-color: #ffebee; }
        .low-stock { background-color: #fff3e0; }
    </style>
</head>
<body>
    <h1>Ticket Sales Report</h1>
    
    <div class="summary">
        <h3>Summary</h3>
        <?php
        $totalEvents = count($events);
        $totalTicketsSold = array_sum(array_column($events, 'tickets_sold'));
        $totalRevenue = array_sum(array_column($events, 'total_revenue'));
        ?>
        <p><strong>Total Events:</strong> <?= $totalEvents ?></p>
        <p><strong>Total Tickets Sold:</strong> <?= $totalTicketsSold ?></p>
        <p><strong>Total Revenue:</strong> $<?= number_format($totalRevenue, 2) ?></p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Event ID</th>
                <th>Event Name</th>
                <th>Date</th>
                <th>Venue</th>
                <th>Price</th>
                <th>Total Tickets</th>
                <th>Sold</th>
                <th>Remaining</th>
                <th>Revenue</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($events as $event): ?>
                <?php
                $soldOut = $event['tickets_remaining'] == 0;
                $lowStock = $event['tickets_remaining'] > 0 && $event['tickets_remaining'] <= ($event['total_tickets'] * 0.1);
                $rowClass = $soldOut ? 'sold-out' : ($lowStock ? 'low-stock' : '');
                ?>
                <tr class="<?= $rowClass ?>">
                    <td><?= htmlspecialchars($event['id']) ?></td>
                    <td><?= htmlspecialchars($event['name']) ?></td>
                    <td><?= date('M j, Y g:i A', strtotime($event['event_date'])) ?></td>
                    <td><?= htmlspecialchars($event['venue']) ?></td>
                    <td>$<?= number_format($event['price'], 2) ?></td>
                    <td><?= $event['total_tickets'] ?></td>
                    <td><?= $event['tickets_sold'] ?></td>
                    <td><?= $event['tickets_remaining'] ?></td>
                    <td>$<?= number_format($event['total_revenue'], 2) ?></td>
                    <td>
                        <?php if ($soldOut): ?>
                            <span style="color: red; font-weight: bold;">SOLD OUT</span>
                        <?php elseif ($lowStock): ?>
                            <span style="color: orange; font-weight: bold;">LOW STOCK</span>
                        <?php else: ?>
                            <span style="color: green;">Available</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>