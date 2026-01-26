<?php
/**
 * Kullanıcı Giriş Test Script
 * Belirli bir email ve şifre ile giriş testi yapar
 */

require_once __DIR__ . '/includes/init.php';

$email = 'gulsultan33@hotmail.com';
$testPassword = '132456';

echo "=== Kullanıcı Giriş Test Script ===\n\n";

$db = Database::getInstance();

// 1. Kullanıcıyı kontrol et
echo "1️⃣ Kullanıcı Kontrolü:\n";
$user = $db->fetch(
    "SELECT kullanici_id, email, ad, soyad, sifre, ilk_giris_zorunlu, aktif, rol_id 
     FROM kullanicilar 
     WHERE email = ?",
    [$email]
);

if (!$user) {
    echo "   ❌ Kullanıcı bulunamadı: $email\n";
    exit(1);
}

echo "   ✅ Kullanıcı bulundu\n";
echo "   ID: {$user['kullanici_id']}\n";
echo "   Email: {$user['email']}\n";
echo "   Ad Soyad: {$user['ad']} {$user['soyad']}\n";
echo "   Aktif: " . ($user['aktif'] ? '✅ EVET' : '❌ HAYIR') . "\n";
echo "   İlk Giriş Zorunlu: " . ($user['ilk_giris_zorunlu'] ? '⚠️ EVET' : '✅ HAYIR') . "\n";
echo "   Rol ID: {$user['rol_id']}\n\n";

// 2. Şifre hash kontrolü
echo "2️⃣ Şifre Hash Kontrolü:\n";
echo "   Hash (ilk 50 karakter): " . substr($user['sifre'], 0, 50) . "...\n";
echo "   Hash uzunluğu: " . strlen($user['sifre']) . " karakter\n";
echo "   Hash algoritması: " . (strpos($user['sifre'], '$2y$') === 0 ? '✅ bcrypt' : '❌ Bilinmeyen') . "\n\n";

// 3. Şifre doğrulama testi
echo "3️⃣ Şifre Doğrulama Testi:\n";
echo "   Test edilen şifre: '$testPassword'\n";

if (password_verify($testPassword, $user['sifre'])) {
    echo "   ✅ ŞİFRE DOĞRU! password_verify() başarılı\n\n";
} else {
    echo "   ❌ ŞİFRE YANLIŞ! password_verify() başarısız\n\n";
    
    // Alternatif şifreler test et
    echo "   🔍 Alternatif şifre testleri:\n";
    $alternatives = [
        '132456',
        ' 132456',
        '132456 ',
        'Gulsultan33',
        'gulsultan33',
    ];
    
    foreach ($alternatives as $alt) {
        if (password_verify($alt, $user['sifre'])) {
            echo "      ✅ Şifre bulundu: '$alt'\n";
        }
    }
    echo "\n";
}

// 4. Rol kontrolü
echo "4️⃣ Rol Kontrolü:\n";
$role = $db->fetch(
    "SELECT rol_id, rol_adi, rol_yetki_seviyesi 
     FROM roller 
     WHERE rol_id = ?",
    [$user['rol_id']]
);

if ($role) {
    echo "   ✅ Rol bulundu\n";
    echo "   Rol Adı: {$role['rol_adi']}\n";
    echo "   Yetki Seviyesi: {$role['rol_yetki_seviyesi']}\n\n";
} else {
    echo "   ❌ Rol bulunamadı! (rol_id: {$user['rol_id']})\n\n";
}

// 5. Login simülasyonu
echo "5️⃣ Login Simülasyonu:\n";
$auth = new Auth();
$loginResult = $auth->login($email, $testPassword);

if ($loginResult === true) {
    echo "   ✅ GİRİŞ BAŞARILI!\n";
    echo "   Session bilgileri:\n";
    echo "      - user_id: " . ($_SESSION['user_id'] ?? 'YOK') . "\n";
    echo "      - user_email: " . ($_SESSION['user_email'] ?? 'YOK') . "\n";
    echo "      - user_role: " . ($_SESSION['user_role'] ?? 'YOK') . "\n";
} elseif ($loginResult === 'password_change_required') {
    echo "   ⚠️ ŞİFRE DEĞİŞTİRME ZORUNLU!\n";
    echo "   Kullanıcının ilk_giris_zorunlu = 1 olduğu için şifre değiştirmesi gerekiyor.\n";
} else {
    echo "   ❌ GİRİŞ BAŞARISIZ!\n";
    echo "   Muhtemel sebepler:\n";
    echo "      - Şifre yanlış\n";
    echo "      - Kullanıcı aktif değil\n";
    echo "      - Email yanlış\n";
}

echo "\n=== Test Tamamlandı ===\n";
