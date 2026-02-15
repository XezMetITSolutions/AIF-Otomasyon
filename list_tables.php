<?php
require_once __DIR__ . '/includes/init.php';
$db = Database::getInstance();
try {
    $tables = $db->fetchAll("SHOW TABLES");
    $list = array_map(function($t) { return array_values($t)[0]; }, $tables);
    file_put_contents('tables_log.txt', implode("\n", $list));
    echo "Tables written to tables_log.txt\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
