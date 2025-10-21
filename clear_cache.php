<?php
// Cache temizleme scripti
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cache Temizlendi</title>
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
    </style>
</head>
<body>
    <div class="container">
        <div class="success">✅ Cache Temizlendi!</div>
        <h2>Tarayıcı Cache'i Temizlendi</h2>
        <p>Artık güncel sidebar'ı görebilirsiniz.</p>
        <a href="admin/dashboard_superadmin.php" class="btn">Admin Paneline Git</a>
        <a href="index.php" class="btn">Ana Sayfaya Git</a>
    </div>
    
    <script>
        // JavaScript ile de cache temizleme
        if ('caches' in window) {
            caches.keys().then(function(names) {
                for (let name of names) {
                    caches.delete(name);
                }
            });
        }
        
        // Local storage temizleme
        localStorage.clear();
        sessionStorage.clear();
        
        // Service Worker temizleme
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(function(registrations) {
                for(let registration of registrations) {
                    registration.unregister();
                }
            });
        }
        
        // IndexedDB temizleme
        if ('indexedDB' in window) {
            indexedDB.databases().then(databases => {
                databases.forEach(db => {
                    indexedDB.deleteDatabase(db.name);
                });
            });
        }
        
        console.log('Cache temizlendi!');
        
        // 3 saniye sonra admin paneline yönlendir
        setTimeout(function() {
            window.location.href = 'admin/dashboard_superadmin.php';
        }, 3000);
    </script>
</body>
</html>
