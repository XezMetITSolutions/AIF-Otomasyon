<?php
/**
 * GitHub Actions Debug Sayfası
 * Bu sayfa ile GitHub Actions'ın neden çalışmadığını debug edebiliriz
 */

// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Sayfa başlığı
$page_title = "GitHub Actions Debug - AIF Otomasyon";
$current_time = date('Y-m-d H:i:s');
$server_info = [
    'PHP Version' => PHP_VERSION,
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Bilinmiyor',
    'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Bilinmiyor',
    'Script Name' => $_SERVER['SCRIPT_NAME'] ?? 'Bilinmiyor',
    'Request URI' => $_SERVER['REQUEST_URI'] ?? 'Bilinmiyor',
    'HTTP Host' => $_SERVER['HTTP_HOST'] ?? 'Bilinmiyor',
    'Server Name' => $_SERVER['SERVER_NAME'] ?? 'Bilinmiyor',
    'Server Port' => $_SERVER['SERVER_PORT'] ?? 'Bilinmiyor',
    'HTTPS' => isset($_SERVER['HTTPS']) ? 'Evet' : 'Hayır',
    'Remote Addr' => $_SERVER['REMOTE_ADDR'] ?? 'Bilinmiyor',
    'User Agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Bilinmiyor'
];

// Dosya sistemi kontrolü
$file_checks = [
    'index.php' => file_exists('index.php'),
    'admin/' => is_dir('admin'),
    'admin/dashboard_superadmin.php' => file_exists('admin/dashboard_superadmin.php'),
    'admin/auth.php' => file_exists('admin/auth.php'),
    'admin/config.php' => file_exists('admin/config.php'),
    'admin/includes/' => is_dir('admin/includes'),
    'admin/includes/database.php' => file_exists('admin/includes/database.php'),
    'admin/includes/byk_manager.php' => file_exists('admin/includes/byk_manager.php'),
    'admin/users/' => is_dir('admin/users'),
    '.github/workflows/deploy.yml' => file_exists('.github/workflows/deploy.yml')
];

// Veritabanı bağlantı testi
$db_test = [];
try {
    if (file_exists('admin/config.php')) {
        require_once 'admin/config.php';
        require_once 'admin/includes/database.php';
        
        $db = Database::getInstance();
        $db_test['connection'] = 'Başarılı';
        $db_test['database_name'] = DB_NAME;
        $db_test['host'] = DB_HOST;
        
        // Tablo kontrolü
        $tables = $db->fetchAll("SHOW TABLES");
        $db_test['tables_count'] = count($tables);
        $db_test['tables'] = array_column($tables, 0);
        
        // Kullanıcı kontrolü
        $users = $db->fetchAll("SELECT username, role, status FROM users LIMIT 5");
        $db_test['users_count'] = count($users);
        $db_test['users'] = $users;
        
    } else {
        $db_test['connection'] = 'config.php bulunamadı';
    }
} catch (Exception $e) {
    $db_test['connection'] = 'Hata: ' . $e->getMessage();
}

// GitHub Actions log kontrolü
$github_logs = [];
$log_files = [
    '.github/workflows/deploy.yml',
    'GITHUB_ACTIONS_FIX.md',
    'FTP_ERROR_FIX.md',
    'DEPLOYMENT_TEST.md'
];

foreach ($log_files as $file) {
    $github_logs[$file] = [
        'exists' => file_exists($file),
        'size' => file_exists($file) ? filesize($file) : 0,
        'modified' => file_exists($file) ? date('Y-m-d H:i:s', filemtime($file)) : 'N/A'
    ];
}

// FTP test (simüle)
$ftp_test = [
    'server' => 'w01dc0ea.kasserver.com',
    'username' => 'f017c2cc',
    'password' => '01528797Mb## (gizli)',
    'port' => 21,
    'status' => 'Test edilemedi (manuel test gerekli)'
];

// Son commit bilgileri
$git_info = [];
if (file_exists('.git/HEAD')) {
    $git_info['git_repo'] = 'Evet';
    $git_info['head'] = file_get_contents('.git/HEAD');
} else {
    $git_info['git_repo'] = 'Hayır';
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .debug-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 20px auto;
            max-width: 1200px;
        }
        
        .debug-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        
        .debug-section {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .debug-section:last-child {
            border-bottom: none;
        }
        
        .status-success {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-error {
            color: #dc3545;
            font-weight: bold;
        }
        
        .status-warning {
            color: #ffc107;
            font-weight: bold;
        }
        
        .code-block {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
        }
        
        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .badge-status {
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="debug-container">
            <!-- Header -->
            <div class="debug-header">
                <h1><i class="fas fa-bug"></i> GitHub Actions Debug</h1>
                <p class="mb-0">AIF Otomasyon - Deployment Sorun Giderme</p>
                <small>Son güncelleme: <?php echo $current_time; ?></small>
            </div>
            
            <!-- Server Bilgileri -->
            <div class="debug-section">
                <h3><i class="fas fa-server"></i> Sunucu Bilgileri</h3>
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <?php foreach ($server_info as $key => $value): ?>
                            <tr>
                                <td><strong><?php echo $key; ?>:</strong></td>
                                <td><?php echo htmlspecialchars($value); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="code-block">
                            <strong>PHP Info:</strong><br>
                            <?php echo "PHP Version: " . PHP_VERSION; ?><br>
                            <?php echo "Memory Limit: " . ini_get('memory_limit'); ?><br>
                            <?php echo "Max Execution Time: " . ini_get('max_execution_time'); ?><br>
                            <?php echo "Upload Max Filesize: " . ini_get('upload_max_filesize'); ?><br>
                            <?php echo "Post Max Size: " . ini_get('post_max_size'); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Dosya Sistemi Kontrolü -->
            <div class="debug-section">
                <h3><i class="fas fa-folder"></i> Dosya Sistemi Kontrolü</h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Dosya/Klasör</th>
                                <th>Durum</th>
                                <th>Boyut</th>
                                <th>Son Değişiklik</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($file_checks as $file => $exists): ?>
                            <tr>
                                <td><code><?php echo $file; ?></code></td>
                                <td>
                                    <?php if ($exists): ?>
                                        <span class="badge bg-success badge-status">✓ Mevcut</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger badge-status">✗ Eksik</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($exists && is_file($file)): ?>
                                        <?php echo number_format(filesize($file)) . ' bytes'; ?>
                                    <?php elseif ($exists && is_dir($file)): ?>
                                        <span class="text-muted">Klasör</span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($exists): ?>
                                        <?php echo date('Y-m-d H:i:s', filemtime($file)); ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Veritabanı Testi -->
            <div class="debug-section">
                <h3><i class="fas fa-database"></i> Veritabanı Bağlantı Testi</h3>
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <?php foreach ($db_test as $key => $value): ?>
                            <tr>
                                <td><strong><?php echo $key; ?>:</strong></td>
                                <td>
                                    <?php if (is_array($value)): ?>
                                        <pre class="mb-0"><?php echo htmlspecialchars(print_r($value, true)); ?></pre>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($value); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="code-block">
                            <strong>Veritabanı Konfigürasyonu:</strong><br>
                            <?php if (defined('DB_HOST')): ?>
                                Host: <?php echo DB_HOST; ?><br>
                                Database: <?php echo DB_NAME; ?><br>
                                User: <?php echo DB_USER; ?><br>
                                Password: <?php echo str_repeat('*', strlen(DB_PASS)); ?><br>
                            <?php else: ?>
                                <span class="status-error">Konfigürasyon bulunamadı</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- GitHub Actions Logs -->
            <div class="debug-section">
                <h3><i class="fab fa-github"></i> GitHub Actions Logs</h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Dosya</th>
                                <th>Durum</th>
                                <th>Boyut</th>
                                <th>Son Değişiklik</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($github_logs as $file => $info): ?>
                            <tr>
                                <td><code><?php echo $file; ?></code></td>
                                <td>
                                    <?php if ($info['exists']): ?>
                                        <span class="badge bg-success badge-status">✓ Mevcut</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger badge-status">✗ Eksik</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($info['size']) . ' bytes'; ?></td>
                                <td><?php echo $info['modified']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- FTP Test -->
            <div class="debug-section">
                <h3><i class="fas fa-upload"></i> FTP Bağlantı Testi</h3>
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <?php foreach ($ftp_test as $key => $value): ?>
                            <tr>
                                <td><strong><?php echo $key; ?>:</strong></td>
                                <td><?php echo htmlspecialchars($value); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> FTP Test Notları:</h6>
                            <ul class="mb-0">
                                <li>FTP bağlantısı manuel olarak test edilmeli</li>
                                <li>FileZilla veya WinSCP kullanılabilir</li>
                                <li>Port 21 (varsayılan) kontrol edilmeli</li>
                                <li>Passive mode gerekebilir</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Git Bilgileri -->
            <div class="debug-section">
                <h3><i class="fab fa-git-alt"></i> Git Bilgileri</h3>
                <div class="code-block">
                    <?php foreach ($git_info as $key => $value): ?>
                    <strong><?php echo $key; ?>:</strong> <?php echo htmlspecialchars($value); ?><br>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Öneriler -->
            <div class="debug-section">
                <h3><i class="fas fa-lightbulb"></i> Öneriler ve Sonraki Adımlar</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle"></i> GitHub Actions Hatası:</h6>
                            <ol>
                                <li>GitHub Repository → Settings → Secrets → Actions</li>
                                <li>FTP_SERVER, FTP_USERNAME, FTP_PASSWORD ekle</li>
                                <li>Actions sekmesinde workflow'u tekrar çalıştır</li>
                                <li>Logs kontrol et</li>
                            </ol>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-success">
                            <h6><i class="fas fa-check-circle"></i> Manuel Test:</h6>
                            <ol>
                                <li>FTP client ile bağlantı test et</li>
                                <li>Dosyaları manuel yükle</li>
                                <li>Web sitesi çalışıyor mu kontrol et</li>
                                <li>Veritabanı bağlantısı test et</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="debug-section text-center">
                <p class="text-muted mb-0">
                    <i class="fas fa-bug"></i> Debug Sayfası - AIF Otomasyon
                    <br>
                    <small>Bu sayfa GitHub Actions sorunlarını gidermek için oluşturulmuştur.</small>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Sayfa yüklendiğinde otomatik refresh (opsiyonel)
        // setTimeout(() => location.reload(), 30000); // 30 saniye
        
        // Console'a debug bilgileri yazdır
        console.log('GitHub Actions Debug Sayfası Yüklendi');
        console.log('Sunucu Bilgileri:', <?php echo json_encode($server_info); ?>);
        console.log('Dosya Kontrolleri:', <?php echo json_encode($file_checks); ?>);
        console.log('Veritabanı Testi:', <?php echo json_encode($db_test); ?>);
    </script>
</body>
</html>
