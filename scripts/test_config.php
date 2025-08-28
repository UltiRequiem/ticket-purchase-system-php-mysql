<?php
require_once 'config/database.php';

echo "Testing database configuration...\n";
echo "Host: " . DB_HOST . "\n";
echo "Database: " . DB_NAME . "\n";
echo "User: " . DB_USER . "\n";
echo "Port: " . DB_PORT . "\n";

try {
    $database = new Database();
    if ($database->testConnection()) {
        echo "✅ Database connection successful!\n";
    } else {
        echo "❌ Database connection failed!\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>