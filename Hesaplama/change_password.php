<?php
session_start();

// Kullanıcı giriş yapmamışsa login'e yönlendir
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

// Şifre değişikliği işlemi
require_once 'admin_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $error = '';

    $error = '';

    // Validasyonlar
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Lütfen tüm alanları doldurun.';
    } elseif (!password_verify($current_password, $users[$user]['password']) && $users[$user]['password'] !== $current_password) {
        $error = 'Mevcut şifre yanlış.';
    } elseif (strlen($new_password) < 8) {
        $error = 'Yeni şifre en az 8 karakter olmalıdır.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Yeni şifreler eşleşmiyor.';
    } elseif ($new_password === $current_password || password_verify($new_password, $users[$user]['password'])) {
        $error = 'Yeni şifre mevcut şifre ile aynı olamaz.';
    } else {
        // Şifre değişikliği başarılı
        $users[$user]['password'] = password_hash($new_password, PASSWORD_DEFAULT);
        $users[$user]['force_password_change'] = false;

        // Değişiklikleri dosyaya kaydet
        save_users($users);

        // Başarı mesajı ile admin dashboard'a yönlendir
        $_SESSION['password_change_success'] = 'Şifre başarıyla değiştirildi!';
        header('Location: admin_dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifre Değiştir | AİF Gider Yönetimi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #009872;
            --primary-dark: #007a5c;
            --bg: #f8fafc;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --white: #ffffff;
            --error: #ef4444;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--bg);
            color: var(--text-dark);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .card {
            background: var(--white);
            width: 100%;
            max-width: 440px;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.04),
                0 8px 10px -6px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--border);
        }

        .header {
            text-align: center;
            margin-bottom: 32px;
        }

        .icon-box {
            width: 56px;
            height: 56px;
            background-color: rgba(0, 152, 114, 0.1);
            color: var(--primary);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 20px;
        }

        .header h1 {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .header p {
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--text-dark);
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.2s ease;
            outline: none;
            background-color: #fbfcfd;
        }

        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(0, 152, 114, 0.1);
        }

        .requirements {
            margin-top: 8px;
            background: #f1f5f9;
            padding: 10px;
            border-radius: 8px;
            font-size: 12px;
            color: var(--text-muted);
        }

        .submit-button {
            width: 100%;
            padding: 14px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 8px;
        }

        .submit-button:hover {
            background-color: var(--primary-dark);
        }

        .error-alert {
            margin-top: 20px;
            padding: 12px;
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
            border-radius: 10px;
            color: var(--error);
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-links {
            text-align: center;
            margin-top: 24px;
            font-size: 13px;
        }

        .footer-links a {
            color: var(--text-muted);
            text-decoration: none;
        }

        .footer-links a:hover {
            color: var(--primary);
        }
    </style>
</head>

<body>

    <div class="card">
        <div class="header">
            <div class="icon-box">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <h1>Şifre Değiştir</h1>
            <p><strong><?php echo htmlspecialchars($user); ?></strong> olarak oturum açtınız. Devam etmek için şifrenizi
                güncellemeniz gerekiyor.</p>
        </div>

        <form method="POST" action="change_password.php">
            <div class="form-group">
                <label for="current_password">Mevcut Şifre</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>

            <div class="form-group">
                <label for="new_password">Yeni Şifre</label>
                <input type="password" id="new_password" name="new_password" required>
                <div class="requirements">
                    • En az 8 karakter<br>
                    • Mevcut şifreden farklı olmalı
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Yeni Şifre (Tekrar)</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="submit-button">Şifreyi Güncelle</button>
        </form>

        <?php if (isset($error) && $error): ?>
            <div class="error-alert">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <div class="footer-links">
            <a href="logout.php">Çıkış Yap</a>
        </div>
    </div>

    <script>
        // Şifre eşleşme kontrolü
        document.getElementById('confirm_password').addEventListener('input', function () {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;

            if (newPassword !== confirmPassword) {
                this.style.borderColor = '#f44336';
            } else {
                this.style.borderColor = '#4CAF50';
            }
        });
    </script>
</body>

</html>