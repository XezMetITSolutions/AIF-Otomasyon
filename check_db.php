<?php
require_once 'includes/init.php';
$db = Database::getInstance();

echo "<h3>Table: kullanicilar</h3>";
$res = $db->fetchAll("DESCRIBE kullanicilar");
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Key</th></tr>";
foreach ($res as $row) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Key']}</td></tr>";
}
echo "</table>";

echo "<h3>Indexes on kullanicilar</h3>";
$res = $db->fetchAll("SHOW INDEX FROM kullanicilar");
echo "<table border='1'><tr><th>Table</th><th>Non_unique</th><th>Key_name</th><th>Column_name</th></tr>";
foreach ($res as $row) {
    echo "<tr><td>{$row['Table']}</td><td>{$row['Non_unique']}</td><td>{$row['Key_name']}</td><td>{$row['Column_name']}</td></tr>";
}
echo "</table>";

echo "<h3>User Count</h3>";
echo $db->fetchColumn("SELECT COUNT(*) FROM kullanicilar");
