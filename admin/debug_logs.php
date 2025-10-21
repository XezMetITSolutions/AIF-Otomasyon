<?php
// Debug logları görüntüleme scripti
header('Content-Type: text/plain');

echo "=== BYK DEBUG LOGLARI ===\n\n";

// Son 50 satırı oku
$logFile = '/var/log/apache2/error.log'; // Linux
if (!file_exists($logFile)) {
    $logFile = 'C:\xampp\apache\logs\error.log'; // Windows XAMPP
}
if (!file_exists($logFile)) {
    $logFile = 'C:\wamp64\logs\apache_error.log'; // Windows WAMP
}
if (!file_exists($logFile)) {
    $logFile = 'error.log'; // Local
}

if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -50);
    
    foreach ($lastLines as $line) {
        if (strpos($line, '[add_user]') !== false || strpos($line, '[update_user]') !== false) {
            echo $line;
        }
    }
} else {
    echo "Log dosyası bulunamadı: $logFile\n";
    echo "Mevcut log dosyaları:\n";
    $files = glob('*.log');
    foreach ($files as $file) {
        echo "- $file\n";
    }
}

echo "\n=== PHP ERROR LOG ===\n";
$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    $lines = file($errorLog);
    $lastLines = array_slice($lines, -20);
    foreach ($lastLines as $line) {
        if (strpos($line, '[add_user]') !== false || strpos($line, '[update_user]') !== false) {
            echo $line;
        }
    }
} else {
    echo "PHP error log bulunamadı\n";
}
?>
