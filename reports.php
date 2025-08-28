<?php
require_once 'vendor/autoload.php';

use TicketFairy\Config\Database;
use TicketFairy\Repositories\EventRepository;
use TicketFairy\Repositories\TicketRepository;

$database = Database::getInstance();
$db = $database->getConnection();
$eventRepo = new EventRepository($db);
$ticketRepo = new TicketRepository($db);

try {
    $reports = $ticketRepo->getTotalSoldByEvent();
} catch (Exception $e) {
    $error_message = "Could not load reports: " . $e->getMessage();
    $reports = [];
}

$totalRevenue = 0;
$totalTicketsSold = 0;
foreach ($reports as $report) {
    $totalRevenue += $report['total_sold'] * ($report['price'] ?? 0);
    $totalTicketsSold += $report['total_sold'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Sales Reports - TicketFairy</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: bold; }
        tr:hover { background-color: #f5f5f5; }
        .summary { background-color: #e9ecef; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .summary h3 { margin-top: 0; }
        .error { color: red; padding: 10px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 20px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <h1>Ticket Sales Reports</h1>
    
    <?php if (isset($error_message)): ?>
        <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    
    <div class="summary">
        <h3>Summary</h3>
        <p><strong>Total Tickets Sold:</strong> <?php echo number_format($totalTicketsSold); ?></p>
        <p><strong>Total Revenue:</strong> $<?php echo number_format($totalRevenue, 2); ?></p>
        <p><strong>Total Events:</strong> <?php echo count($reports); ?></p>
    </div>
    
    <h2>Sales by Event</h2>
    
    <?php if (empty($reports)): ?>
        <p>No events found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Event Name</th>
                    <th>Event ID</th>
                    <th class="text-right">Tickets Sold</th>
                    <th class="text-right">Price per Ticket</th>
                    <th class="text-right">Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                    <?php 
                        $revenue = ($report['total_sold'] ?? 0) * ($report['price'] ?? 0);
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($report['name']); ?></td>
                        <td class="text-center"><?php echo $report['id']; ?></td>
                        <td class="text-right"><?php echo number_format($report['total_sold'] ?? 0); ?></td>
                        <td class="text-right">$<?php echo number_format($report['price'] ?? 0, 2); ?></td>
                        <td class="text-right">$<?php echo number_format($revenue, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <p style="margin-top: 30px;">
        <a href="purchase_ticket.php">← Back to Purchase Tickets</a>
    </p>
</body>
</html>