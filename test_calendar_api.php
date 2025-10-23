<?php
require_once 'admin/includes/database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    // Test database connection
    $testSql = "SELECT 1 as test";
    $testResult = $db->fetch($testSql);
    
    if (!$testResult) {
        throw new Exception("Database connection failed");
    }
    
    // Check if calendar_events table exists
    $tableCheckSql = "SHOW TABLES LIKE 'calendar_events'";
    $tableExists = $db->fetch($tableCheckSql);
    
    if (!$tableExists) {
        echo json_encode([
            'success' => false,
            'message' => 'calendar_events table does not exist'
        ]);
        exit;
    }
    
    // Get total events count
    $countSql = "SELECT COUNT(*) as total FROM calendar_events";
    $totalEvents = $db->fetch($countSql);
    
    // Get sample events
    $sampleSql = "SELECT * FROM calendar_events LIMIT 5";
    $sampleEvents = $db->fetchAll($sampleSql);
    
    echo json_encode([
        'success' => true,
        'database_connected' => true,
        'table_exists' => true,
        'total_events' => $totalEvents['total'],
        'sample_events' => $sampleEvents
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => true
    ]);
}
?>