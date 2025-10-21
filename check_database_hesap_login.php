<?php
// Veritabanında Hesap/login.php referansı arama scripti
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'admin/includes/database.php';
    $db = Database::getInstance();
    
    echo "<h2>🔍 Veritabanında Hesap/login.php Referansı Arama</h2>";
    echo "<hr>";
    
    // Tüm tabloları listele
    $tables = $db->fetchAll("SHOW TABLES");
    echo "<h3>📋 Veritabanındaki Tablolar:</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        echo "<li>$tableName</li>";
    }
    echo "</ul>";
    
    echo "<hr>";
    echo "<h3>🔎 Hesap/login.php Referansı Arama:</h3>";
    
    $foundReferences = false;
    
    // Her tabloyu kontrol et
    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        
        // Tablonun kolonlarını al
        $columns = $db->fetchAll("SHOW COLUMNS FROM `$tableName`");
        
        foreach ($columns as $column) {
            $columnName = $column['Field'];
            $columnType = $column['Type'];
            
            // Sadece text/varchar kolonları kontrol et
            if (strpos($columnType, 'text') !== false || 
                strpos($columnType, 'varchar') !== false || 
                strpos($columnType, 'char') !== false) {
                
                // Bu kolonda Hesap/login.php ara
                $sql = "SELECT * FROM `$tableName` WHERE `$columnName` LIKE '%Hesap/login%' OR `$columnName` LIKE '%Hesap%login%'";
                
                try {
                    $results = $db->fetchAll($sql);
                    
                    if (!empty($results)) {
                        $foundReferences = true;
                        echo "<div style='background: #ffebee; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #f44336;'>";
                        echo "<h4>🚨 BULUNDU! Tablo: $tableName, Kolon: $columnName</h4>";
                        echo "<p><strong>Toplam bulunan kayıt:</strong> " . count($results) . "</p>";
                        
                        foreach ($results as $i => $result) {
                            echo "<div style='background: white; padding: 10px; margin: 5px 0; border-radius: 3px;'>";
                            echo "<strong>Kayıt " . ($i + 1) . ":</strong><br>";
                            
                            foreach ($result as $key => $value) {
                                if (strpos($value, 'Hesap') !== false || strpos($value, 'login') !== false) {
                                    echo "<span style='color: red; font-weight: bold;'>$key: $value</span><br>";
                                } else {
                                    echo "$key: $value<br>";
                                }
                            }
                            echo "</div>";
                        }
                        echo "</div>";
                    }
                } catch (Exception $e) {
                    // Bu kolonda arama yapılamadı, sessizce devam et
                }
            }
        }
    }
    
    if (!$foundReferences) {
        echo "<div style='background: #e8f5e8; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #4caf50;'>";
        echo "<h4>✅ Temiz!</h4>";
        echo "<p>Veritabanında <strong>Hesap/login.php</strong> referansı bulunamadı.</p>";
        echo "</div>";
    }
    
    echo "<hr>";
    echo "<h3>🔍 Ek Kontroller:</h3>";
    
    // Özel kontroller
    $specialChecks = [
        'users' => ['redirect_url', 'login_redirect', 'default_page'],
        'settings' => ['login_page', 'redirect_url', 'default_redirect'],
        'sessions' => ['redirect_url', 'login_page'],
        'config' => ['login_url', 'redirect_url']
    ];
    
    foreach ($specialChecks as $tableName => $columns) {
        // Tablo var mı kontrol et
        $tableExists = $db->fetchOne("SHOW TABLES LIKE '$tableName'");
        
        if ($tableExists) {
            echo "<h4>📋 $tableName tablosu kontrol ediliyor...</h4>";
            
            foreach ($columns as $column) {
                // Kolon var mı kontrol et
                $columnExists = $db->fetchOne("SHOW COLUMNS FROM `$tableName` LIKE '$column'");
                
                if ($columnExists) {
                    $sql = "SELECT * FROM `$tableName` WHERE `$column` LIKE '%Hesap%' OR `$column` LIKE '%login%'";
                    $results = $db->fetchAll($sql);
                    
                    if (!empty($results)) {
                        echo "<div style='background: #fff3cd; padding: 10px; margin: 5px 0; border-radius: 3px;'>";
                        echo "<strong>$tableName.$column:</strong> " . count($results) . " kayıt bulundu<br>";
                        foreach ($results as $result) {
                            echo "ID: " . ($result['id'] ?? 'N/A') . " - " . $result[$column] . "<br>";
                        }
                        echo "</div>";
                    }
                }
            }
        }
    }
    
    echo "<hr>";
    echo "<h3>📊 Özet:</h3>";
    echo "<p>Veritabanı taraması tamamlandı. Eğer yukarıda kırmızı uyarılar görüyorsanız, o kayıtları düzeltmeniz gerekebilir.</p>";
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #f44336;'>";
    echo "<h4>❌ Hata!</h4>";
    echo "<p>Veritabanı bağlantısı kurulamadı: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veritabanı Hesap/login.php Taraması</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        h2, h3, h4 {
            color: #333;
        }
        hr {
            border: none;
            border-top: 2px solid #ddd;
            margin: 20px 0;
        }
        .refresh-btn {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 10px 0;
        }
        .refresh-btn:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <a href="?" class="refresh-btn">🔄 Yeniden Tara</a>
    <a href="admin/dashboard_superadmin.php" class="refresh-btn">🏠 Admin Paneli</a>
</body>
</html>