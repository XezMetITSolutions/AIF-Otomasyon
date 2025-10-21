<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session başlat
session_start();

// Database bağlantısını test et
try {
    require_once 'admin/includes/database.php';
    $db = Database::getInstance();
    $dbConnected = true;
} catch (Exception $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
}

// Login test fonksiyonu
function testLogin($username, $password) {
    $results = [];
    
    try {
        require_once 'admin/includes/database.php';
        $db = Database::getInstance();
        
        // 1. Kullanıcıyı bul
        $user = $db->fetchOne(
            "SELECT * FROM users WHERE username = ?",
            [$username]
        );
        
        $results['user_found'] = $user ? true : false;
        $results['user_data'] = $user;
        
        if ($user) {
            // 2. Kullanıcı durumu kontrol et
            $results['user_status'] = $user['status'];
            $results['user_active'] = $user['status'] === 'active';
            
            // 3. Şifre hash'ini kontrol et
            $results['password_hash'] = $user['password_hash'] ?? $user['password'] ?? null;
            $results['password_hash_length'] = strlen($results['password_hash'] ?? '');
            $results['password_hash_start'] = substr($results['password_hash'] ?? '', 0, 20) . '...';
            
            // 4. Şifre doğrulaması
            $results['password_verify'] = password_verify($password, $results['password_hash']);
            
            // 5. Yeni şifre hash'i oluştur
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $results['new_password_hash'] = $newHash;
            $results['new_password_hash_length'] = strlen($newHash);
            $results['new_password_hash_start'] = substr($newHash, 0, 20) . '...';
            
            // 6. Yeni hash ile doğrulama
            $results['new_password_verify'] = password_verify($password, $newHash);
            
            // 7. Kullanıcı rolü
            $results['user_role'] = $user['role'];
            
            // 8. Son giriş zamanı
            $results['last_login'] = $user['last_login'];
            
        } else {
            $results['error'] = 'Kullanıcı bulunamadı';
        }
        
    } catch (Exception $e) {
        $results['error'] = $e->getMessage();
    }
    
    return $results;
}

// POST işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testUsername = $_POST['test_username'] ?? '';
    $testPassword = $_POST['test_password'] ?? '';
    
    if ($testUsername && $testPassword) {
        $loginTest = testLogin($testUsername, $testPassword);
    }
}

// Veritabanından tüm kullanıcıları çek
$allUsers = [];
if ($dbConnected) {
    try {
        $allUsers = $db->fetchAll("SELECT id, username, email, first_name, last_name, role, status, last_login, created_at FROM users ORDER BY id");
    } catch (Exception $e) {
        $allUsersError = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Debug Sayfası</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .debug-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .debug-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .debug-header {
            background: #007bff;
            color: white;
            padding: 15px 20px;
            font-weight: 600;
        }
        
        .debug-body {
            padding: 20px;
        }
        
        .test-form {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .result-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
        }
        
        .success {
            color: #28a745;
            font-weight: 600;
        }
        
        .error {
            color: #dc3545;
            font-weight: 600;
        }
        
        .warning {
            color: #ffc107;
            font-weight: 600;
        }
        
        .info {
            color: #17a2b8;
            font-weight: 600;
        }
        
        pre {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            font-size: 0.9rem;
            overflow-x: auto;
        }
        
        .user-table {
            font-size: 0.9rem;
        }
        
        .user-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .status-badge {
            font-size: 0.8rem;
        }
        
        .role-badge {
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="debug-container">
        <h1 class="text-center mb-4">
            <i class="fas fa-bug text-primary"></i> Login Debug Sayfası
        </h1>
        
        <!-- Database Connection Status -->
        <div class="debug-card">
            <div class="debug-header">
                <i class="fas fa-database me-2"></i>Veritabanı Bağlantısı
            </div>
            <div class="debug-body">
                <?php if ($dbConnected): ?>
                    <div class="success">
                        <i class="fas fa-check-circle me-2"></i>Veritabanı bağlantısı başarılı
                    </div>
                <?php else: ?>
                    <div class="error">
                        <i class="fas fa-times-circle me-2"></i>Veritabanı bağlantısı başarısız: <?php echo htmlspecialchars($dbError); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Login Test Form -->
        <div class="debug-card">
            <div class="debug-header">
                <i class="fas fa-sign-in-alt me-2"></i>Login Test
            </div>
            <div class="debug-body">
                <form method="POST" class="test-form">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="test_username" class="form-label">Kullanıcı Adı</label>
                            <input type="text" class="form-control" id="test_username" name="test_username" 
                                   value="<?php echo htmlspecialchars($_POST['test_username'] ?? 'debug.test'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="test_password" class="form-label">Şifre</label>
                            <input type="password" class="form-control" id="test_password" name="test_password" 
                                   value="<?php echo htmlspecialchars($_POST['test_password'] ?? 'Test123456'); ?>" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-play me-2"></i>Login Test Yap
                        </button>
                    </div>
                </form>
                
                <?php if (isset($loginTest)): ?>
                    <div class="result-box">
                        <h5><i class="fas fa-clipboard-list me-2"></i>Test Sonuçları</h5>
                        
                        <?php if (isset($loginTest['error'])): ?>
                            <div class="error">
                                <i class="fas fa-times-circle me-2"></i>Hata: <?php echo htmlspecialchars($loginTest['error']); ?>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Kullanıcı Bilgileri</h6>
                                    <ul class="list-unstyled">
                                        <li><strong>Kullanıcı Bulundu:</strong> 
                                            <span class="<?php echo $loginTest['user_found'] ? 'success' : 'error'; ?>">
                                                <?php echo $loginTest['user_found'] ? 'Evet' : 'Hayır'; ?>
                                            </span>
                                        </li>
                                        <?php if ($loginTest['user_found']): ?>
                                            <li><strong>Durum:</strong> 
                                                <span class="<?php echo $loginTest['user_active'] ? 'success' : 'warning'; ?>">
                                                    <?php echo htmlspecialchars($loginTest['user_status']); ?>
                                                </span>
                                            </li>
                                            <li><strong>Rol:</strong> 
                                                <span class="info"><?php echo htmlspecialchars($loginTest['user_role']); ?></span>
                                            </li>
                                            <li><strong>Son Giriş:</strong> 
                                                <?php echo htmlspecialchars($loginTest['last_login'] ?? 'Hiç'); ?>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Şifre Bilgileri</h6>
                                    <ul class="list-unstyled">
                                        <?php if ($loginTest['user_found']): ?>
                                            <li><strong>Hash Uzunluğu:</strong> <?php echo $loginTest['password_hash_length']; ?></li>
                                            <li><strong>Hash Başlangıcı:</strong> <code><?php echo htmlspecialchars($loginTest['password_hash_start']); ?></code></li>
                                            <li><strong>Şifre Doğrulaması:</strong> 
                                                <span class="<?php echo $loginTest['password_verify'] ? 'success' : 'error'; ?>">
                                                    <?php echo $loginTest['password_verify'] ? 'Başarılı' : 'Başarısız'; ?>
                                                </span>
                                            </li>
                                            <li><strong>Yeni Hash Doğrulaması:</strong> 
                                                <span class="<?php echo $loginTest['new_password_verify'] ? 'success' : 'error'; ?>">
                                                    <?php echo $loginTest['new_password_verify'] ? 'Başarılı' : 'Başarısız'; ?>
                                                </span>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                            
                            <h6 class="mt-3">Detaylı Veri</h6>
                            <pre><?php echo json_encode($loginTest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- All Users -->
        <div class="debug-card">
            <div class="debug-header">
                <i class="fas fa-users me-2"></i>Tüm Kullanıcılar
            </div>
            <div class="debug-body">
                <?php if (isset($allUsersError)): ?>
                    <div class="error">
                        <i class="fas fa-times-circle me-2"></i>Hata: <?php echo htmlspecialchars($allUsersError); ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped user-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Kullanıcı Adı</th>
                                    <th>E-posta</th>
                                    <th>Ad Soyad</th>
                                    <th>Rol</th>
                                    <th>Durum</th>
                                    <th>Son Giriş</th>
                                    <th>Oluşturulma</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allUsers as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><code><?php echo htmlspecialchars($user['username']); ?></code></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                        <td>
                                            <span class="badge bg-info role-badge">
                                                <?php echo htmlspecialchars($user['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $user['status'] === 'active' ? 'bg-success' : 'bg-warning'; ?> status-badge">
                                                <?php echo htmlspecialchars($user['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['last_login'] ?? 'Hiç'); ?></td>
                                        <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- System Info -->
        <div class="debug-card">
            <div class="debug-header">
                <i class="fas fa-info-circle me-2"></i>Sistem Bilgileri
            </div>
            <div class="debug-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>PHP Bilgileri</h6>
                        <ul class="list-unstyled">
                            <li><strong>PHP Versiyonu:</strong> <?php echo phpversion(); ?></li>
                            <li><strong>password_hash() Fonksiyonu:</strong> 
                                <span class="<?php echo function_exists('password_hash') ? 'success' : 'error'; ?>">
                                    <?php echo function_exists('password_hash') ? 'Mevcut' : 'Yok'; ?>
                                </span>
                            </li>
                            <li><strong>password_verify() Fonksiyonu:</strong> 
                                <span class="<?php echo function_exists('password_verify') ? 'success' : 'error'; ?>">
                                    <?php echo function_exists('password_verify') ? 'Mevcut' : 'Yok'; ?>
                                </span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Session Bilgileri</h6>
                        <ul class="list-unstyled">
                            <li><strong>Session ID:</strong> <?php echo session_id(); ?></li>
                            <li><strong>Session Durumu:</strong> 
                                <span class="<?php echo session_status() === PHP_SESSION_ACTIVE ? 'success' : 'error'; ?>">
                                    <?php echo session_status() === PHP_SESSION_ACTIVE ? 'Aktif' : 'Pasif'; ?>
                                </span>
                            </li>
                            <li><strong>Session Verisi:</strong> 
                                <?php if (empty($_SESSION)): ?>
                                    <span class="warning">Boş</span>
                                <?php else: ?>
                                    <pre><?php echo json_encode($_SESSION, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="debug-card">
            <div class="debug-header">
                <i class="fas fa-bolt me-2"></i>Hızlı İşlemler
            </div>
            <div class="debug-body">
                <div class="row">
                    <div class="col-md-4">
                        <a href="index.php" class="btn btn-outline-primary w-100 mb-2">
                            <i class="fas fa-home me-2"></i>Ana Sayfa
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="byk_debug_root.php" class="btn btn-outline-info w-100 mb-2">
                            <i class="fas fa-flask me-2"></i>BYK Debug
                        </a>
                    </div>
                    <div class="col-md-4">
                        <button onclick="location.reload()" class="btn btn-outline-success w-100 mb-2">
                            <i class="fas fa-refresh me-2"></i>Sayfayı Yenile
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
