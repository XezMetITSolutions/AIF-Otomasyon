<?php
// Güçlü cache ve session temizleme scripti
session_start();

// Tüm session verilerini temizle
$_SESSION = array();

// Session cookie'sini sil
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Session'ı tamamen yok et
session_destroy();

// Cache temizleme header'ları
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('ETag: "' . md5(time()) . '"');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cache ve Session Temizleme</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            color: white;
        }
        .container {
            background: rgba(255,255,255,0.1);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            backdrop-filter: blur(10px);
            max-width: 600px;
        }
        .success {
            color: #4CAF50;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .btn {
            background: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 10px;
        }
        .btn:hover {
            background: #45a049;
        }
        .status {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success">🧹 Cache ve Session Temizleme</div>
        <h2>Tüm Cache ve Session Verileri Temizlendi</h2>
        
        <div class="status">
            <h4>✅ Temizlenen Veriler:</h4>
            <ul>
                <li>PHP Session verileri</li>
                <li>Session cookie'leri</li>
                <li>Tarayıcı cache'i</li>
                <li>Local storage</li>
                <li>Session storage</li>
                <li>Service Worker</li>
                <li>IndexedDB</li>
            </ul>
        </div>
        
        <p>Artık ana sayfaya yönlendirileceksiniz.</p>
        
        <a href="index.php" class="btn">🏠 Ana Sayfaya Git</a>
        <a href="admin/dashboard_superadmin.php" class="btn">👨‍💼 Admin Paneli</a>
    </div>
    
    <script>
        // JavaScript ile de cache temizleme
        if ('caches' in window) {
            caches.keys().then(function(names) {
                for (let name of names) {
                    caches.delete(name);
                }
                console.log('Cache temizlendi:', names);
            });
        }
        
        // Local storage temizleme
        localStorage.clear();
        sessionStorage.clear();
        console.log('Local ve Session storage temizlendi');
        
        // Service Worker temizleme
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(function(registrations) {
                for(let registration of registrations) {
                    registration.unregister();
                }
                console.log('Service Worker temizlendi:', registrations.length);
            });
        }
        
        // IndexedDB temizleme
        if ('indexedDB' in window) {
            indexedDB.databases().then(databases => {
                databases.forEach(db => {
                    indexedDB.deleteDatabase(db.name);
                });
                console.log('IndexedDB temizlendi:', databases.length);
            });
        }
        
        // Cookie temizleme
        document.cookie.split(";").forEach(function(c) { 
            document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
        });
        console.log('Cookie\'ler temizlendi');
        
        console.log('Tüm cache temizleme işlemi tamamlandı!');
        
        // 3 saniye sonra ana sayfaya yönlendir
        setTimeout(function() {
            window.location.href = 'index.php';
        }, 3000);
    </script>
</body>
</html>
