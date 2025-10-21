<?php
session_start();

// Eğer zaten giriş yapmışsa dashboard'a yönlendir
if (isset($_SESSION['username'])) {
    require_once 'admin/auth.php';
    $user = SessionManager::getCurrentUser();
    if ($user) {
        if ($user['role'] === 'superadmin') {
            header('Location: admin/dashboard_superadmin.php');
        } elseif ($user['role'] === 'manager') {
            header('Location: manager/dashboard_manager.php');
        } else {
            header('Location: users/dashboard_member.php');
        }
        exit;
    }
}

// Login işlemi
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Kullanıcı adı ve şifre gereklidir.';
    } else {
        try {
            require_once 'admin/includes/database.php';
            
            // Basit veritabanı sorgusu
            $db = Database::getInstance();
            $user = $db->fetchOne(
                "SELECT * FROM users WHERE username = ? AND status = 'active'",
                [$username]
            );
            
            if ($user && password_verify($password, $user['password_hash'] ?? $user['password'])) {
                // Giriş başarılı
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                
                // Son giriş zamanını güncelle
                $db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
                
                // Role göre yönlendirme
                if ($user['role'] === 'superadmin') {
                    header('Location: admin/dashboard_superadmin.php');
                } elseif ($user['role'] === 'manager') {
                    header('Location: manager/dashboard_manager.php');
                } else {
                    header('Location: users/dashboard_member.php');
                }
                exit;
            } else {
                $error = 'Kullanıcı adı veya şifre hatalı.';
            }
        } catch (Exception $e) {
            $error = 'Giriş yapılırken bir hata oluştu: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIF Otomasyon - Giriş</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo i {
            font-size: 3rem;
            color: #009872;
            margin-bottom: 15px;
        }
        
        .logo h1 {
            color: #333;
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
        }
        
        .logo p {
            color: #666;
            font-size: 0.9rem;
            margin: 5px 0 0 0;
        }
        
        .form-floating {
            margin-bottom: 20px;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 1rem 0.75rem;
            height: auto;
        }
        
        .form-control:focus {
            border-color: #009872;
            box-shadow: 0 0 0 0.2rem rgba(0, 152, 114, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #009872, #00b085);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            font-size: 1rem;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 152, 114, 0.3);
            color: white;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .login-footer a {
            color: #009872;
            text-decoration: none;
        }
        
        .login-footer a:hover {
            color: #007a5e;
        }
        
        /* Debug info */
        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin-top: 20px;
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .debug-info h6 {
            color: #495057;
            margin-bottom: 10px;
        }
        
        .debug-info pre {
            background: #e9ecef;
            padding: 10px;
            border-radius: 3px;
            margin: 0;
            font-size: 0.7rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Logo Section -->
        <div class="logo">
            <i class="fas fa-building"></i>
            <h1>AIF Otomasyon</h1>
            <p>Sistem Girişi</p>
        </div>

        <!-- Error/Success Messages -->
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
        <div class="alert alert-success" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" id="loginForm">
            <div class="form-floating">
                <input type="text" class="form-control" id="username" name="username" placeholder="Kullanıcı Adı" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                <label for="username">
                    <i class="fas fa-user me-2"></i>Kullanıcı Adı
                </label>
            </div>

            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="Şifre" required>
                <label for="password">
                    <i class="fas fa-lock me-2"></i>Şifre
                </label>
            </div>

            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
            </button>
        </form>

        <!-- Debug Info -->
        <div class="debug-info">
            <h6><i class="fas fa-bug me-2"></i>Debug Bilgileri</h6>
            <p><strong>Test Kullanıcıları:</strong></p>
            <ul>
                <li><code>debug.test</code> / <code>Test123456</code> (Manager)</li>
                <li><code>AIF-Admin</code> / <code>admin123</code> (Superadmin)</li>
                <li><code>mete.burcak</code> / <code>mete123</code> (Manager)</li>
            </ul>
            <p><strong>Session:</strong> <?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'Yok'; ?></p>
            <p><strong>Method:</strong> <?php echo $_SERVER['REQUEST_METHOD']; ?></p>
            <p><strong>Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>

        <!-- Footer -->
        <div class="login-footer">
            <p>&copy; 2024 AIF Otomasyon. Tüm hakları saklıdır.</p>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-focus on username field
        document.getElementById('username').focus();

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert('Kullanıcı adı ve şifre gereklidir.');
                return false;
            }
        });

        // Enter key handling
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').submit();
            }
        });
    </script>
</body>
</html>