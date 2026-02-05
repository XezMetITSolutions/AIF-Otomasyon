<?php
/**
 * Migration Runner - Şube Ziyaretleri Sistemi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Migration - Şube Ziyaretleri</title></head><body>";
echo "<h1>Running Migration: 006_sube_ziyaretleri</h1>";
echo "<pre>";

$db = Database::getInstance();
$sqlFile = __DIR__ . '/migrations/006_sube_ziyaretleri.sql';

if (!file_exists($sqlFile)) {
    die("Error: SQL file not found at $sqlFile");
}

try {
    $sql = file_get_contents($sqlFile);
    
    // Split by semicolon and execute each statement
    // Simple split by semicolon. For more complex SQL, a specialized parser would be better.
    $statements = preg_split('/;(?=(?:[^\'"]*[\'"][^\'"]*[\'"])*[^\'"]*$)/', $sql);
    
    $executed = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        // Skip empty statements and comments
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $db->query($statement);
            $executed++;
            echo "✓ Executed: " . substr($statement, 0, 80) . "...\n";
        } catch (Exception $e) {
            $errors++;
            echo "✗ Error: " . $e->getMessage() . "\n";
            echo "  Statement: " . substr($statement, 0, 100) . "...\n";
        }
    }
    
    echo "\n";
    echo "=================================\n";
    echo "Migration completed!\n";
    echo "Executed: $executed statements\n";
    echo "Errors: $errors\n";
    echo "=================================\n";
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
echo "<p><a href='/admin/dashboard.php'>Back to Dashboard</a></p>";
echo "</body></html>";
