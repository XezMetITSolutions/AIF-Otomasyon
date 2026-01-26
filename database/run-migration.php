<?php
/**
 * Migration Runner - Meeting Management System
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Migration</title></head><body>";
echo "<h1>Running Migration: 004_meeting_management_system</h1>";
echo "<pre>";

$db = Database::getInstance();

try {
    // SQL Code Embedded directly to avoid file not found issues
    $sql = '
SET @dbname = DATABASE();
SET @tablename = "toplanti_gundem";

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (table_name = @tablename)
   AND (table_schema = @dbname)
   AND (column_name = "gorusme_notlari")) > 0,
  "SELECT 1",
  "ALTER TABLE toplanti_gundem ADD COLUMN gorusme_notlari TEXT NULL AFTER aciklama"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
';
    
    // $sql = file_get_contents($sqlFile);
    
    // Split by semicolon and execute each statement
    $statements = explode(';', $sql);
    
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
