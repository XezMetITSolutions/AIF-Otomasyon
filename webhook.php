<?php
/**
 * GitHub Webhook Handler
 * GitHub'a push edildiğinde otomatik FTP upload
 */

// GitHub webhook secret (opsiyonel)
$webhook_secret = 'your_webhook_secret_here';

// FTP bilgileri
$ftp_server = 'aifcrm.metechnik.at';
$ftp_username = 'd0451622';
$ftp_password = '01528797Mb##';

// GitHub payload'ını al
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

// Webhook doğrulama (opsiyonel)
if ($webhook_secret) {
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    $expected = 'sha256=' . hash_hmac('sha256', $payload, $webhook_secret);
    
    if (!hash_equals($expected, $signature)) {
        http_response_code(401);
        exit('Unauthorized');
    }
}

// Sadece main branch'e push edildiğinde çalış
if ($data['ref'] === 'refs/heads/main') {
    echo "Deployment başlatılıyor...\n";
    
    // Git pull
    exec('git pull origin main 2>&1', $output, $return_code);
    
    if ($return_code === 0) {
        echo "Git pull başarılı\n";
        
        // FTP upload
        $ftp_connection = ftp_connect($ftp_server);
        
        if ($ftp_connection) {
            $login = ftp_login($ftp_connection, $ftp_username, $ftp_password);
            
            if ($login) {
                echo "FTP bağlantısı başarılı\n";
                
                // Dosyaları yükle
                uploadDirectory($ftp_connection, '.', '/public_html/');
                
                ftp_close($ftp_connection);
                echo "Deployment tamamlandı!\n";
            } else {
                echo "FTP giriş hatası\n";
            }
        } else {
            echo "FTP bağlantı hatası\n";
        }
    } else {
        echo "Git pull hatası: " . implode("\n", $output) . "\n";
    }
} else {
    echo "Main branch değil, deployment atlanıyor\n";
}

function uploadDirectory($ftp_connection, $local_dir, $remote_dir) {
    $files = scandir($local_dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $local_path = $local_dir . '/' . $file;
        $remote_path = $remote_dir . $file;
        
        if (is_dir($local_path)) {
            // Klasör oluştur
            ftp_mkdir($ftp_connection, $remote_path);
            uploadDirectory($ftp_connection, $local_path, $remote_path . '/');
        } else {
            // Dosya yükle
            ftp_put($ftp_connection, $remote_path, $local_path, FTP_BINARY);
        }
    }
}
?>
