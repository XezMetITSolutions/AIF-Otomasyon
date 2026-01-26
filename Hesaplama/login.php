<?php
session_start();

// Benutzer-Daten aus Konfigurationsdatei laden
require_once 'admin_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Kullanıcı adını küçük harfe çevirerek tara (case-insensitive)
    $foundUser = null;
    $inputUserLower = strtolower($username);

    foreach ($users as $key => $data) {
        if (strtolower($key) === $inputUserLower) {
            $foundUser = $key;
            break;
        }
    }

    // Überprüfen, ob der Benutzer existiert und das Passwort korrekt ist
    if ($foundUser && ($users[$foundUser]['password'] === $password || password_verify($password, $users[$foundUser]['password']))) {
        $_SESSION['user'] = $foundUser; // Orijinal halini session'a kaydet (örn: MuhasebeAT)

        // İlk girişte şifre değişikliği gerekli mi kontrol et
        if ($users[$foundUser]['force_password_change']) {
            header('Location: change_password.php');
            exit;
        }

        header('Location: admin_dashboard.php');
        exit;
    } else {
        $error = 'Geçersiz kullanıcı adı veya şifre';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş | AİF Gider Yönetimi</title>
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

        .login-card {
            background: var(--white);
            width: 100%;
            max-width: 440px;
            padding: 48px;
            border-radius: 24px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.04),
                0 8px 10px -6px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--border);
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo-placeholder {
            width: 64px;
            height: 64px;
            background-color: rgba(0, 152, 114, 0.1);
            color: var(--primary);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 24px;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .header p {
            color: var(--text-muted);
            font-size: 15px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--text-dark);
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.2s ease;
            outline: none;
            background-color: #fbfcfd;
        }

        .form-group input:focus {
            border-color: var(--primary);
            background-color: var(--white);
            box-shadow: 0 0 0 4px rgba(0, 152, 114, 0.1);
        }

        .login-button {
            width: 100%;
            padding: 16px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 12px;
        }

        .login-button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 152, 114, 0.2);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .error-alert {
            margin-top: 24px;
            padding: 12px 16px;
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
            border-radius: 12px;
            color: var(--error);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-text {
            text-align: center;
            margin-top: 32px;
            font-size: 13px;
            color: var(--text-muted);
        }

        /* Subtle animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-card {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>

<body>

    <div class="login-card">
        <div class="header">
            <div class="logo-placeholder">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
            </div>
            <h1>Yönetim Paneli</h1>
            <p>Devam etmek için lütfen giriş yapın.</p>
        </div>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Kullanıcı Adı</label>
                <input type="text" id="username" name="username" placeholder="Kullanıcı adınızı girin" required
                    autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Şifre</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required
                    autocomplete="current-password">
            </div>
            <button type="submit" class="login-button">Giriş Yap</button>
        </form>

        <?php if (isset($error)): ?>
            <div class="error-alert">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <p class="footer-text">&copy; <?php echo date('Y'); ?> Avusturya İslam Federasyonu</p>
    </div>

</body>

</html>