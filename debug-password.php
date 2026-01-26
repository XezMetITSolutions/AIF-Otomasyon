<?php
/**
 * Kullanıcı Şifre Kontrol ve Debug Script
 * Bu script belirli bir kullanıcının şifresini kontrol eder ve gerekirse sıfırlar
 */

require_once __DIR__ . '/includes/init.php';

$email = 'gulsultan33@hotmail.com';

$db = Database::getInstance();

// Kullanıcıyı bul
$user = $db->fetch(
    "SELECT kullanici_id, email, ad, soyad, ilk_giris_zorunlu, aktif, rol_id 
     FROM kullanicilar 
     WHERE email = ?",
    [$email]
);

if (!$user) {
    echo "❌ Kullanıcı bulunamadı: $email\n";
    exit(1);
}

echo "✅ Kullanıcı Bulundu:\n";
echo "   ID: {$user['kullanici_id']}\n";
echo "   Email: {$user['email']}\n";
echo "   Ad Soyad: {$user['ad']} {$user['soyad']}\n";
echo "   İlk Giriş Zorunlu: " . ($user['ilk_giris_zorunlu'] ? 'EVET' : 'HAYIR') . "\n";
echo "   Aktif: " . ($user['aktif'] ? 'EVET' : 'HAYIR') . "\n";
echo "   Rol ID: {$user['rol_id']}\n\n";

// Şifre sıfırlama seçeneği
echo "Bu kullanıcının şifresini sıfırlamak istiyor musunuz? (y/n): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim($line) !== 'y') {
    echo "İşlem iptal edildi.\n";
    exit(0);
}

echo "Yeni şifre girin: ";
$handle = fopen("php://stdin", "r");
$newPassword = trim(fgets($handle));
fclose($handle);

if (strlen($newPassword) < 8) {
    echo "❌ Şifre en az 8 karakter olmalıdır.\n";
    exit(1);
}

// Şifreyi güncelle
try {
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->query(
        "UPDATE kullanicilar SET sifre = ?, ilk_giris_zorunlu = 0 WHERE kullanici_id = ?",
        [$hashedPassword, $user['kullanici_id']]
    );
    
    $rowCount = $stmt->rowCount();
    
    if ($rowCount > 0) {
        echo "✅ Şifre başarıyla güncellendi!\n";
        echo "   Etkilenen satır sayısı: $rowCount\n";
        echo "   İlk giriş zorunluluğu kaldırıldı.\n";
        
        // Doğrulama
        $updatedUser = $db->fetch(
            "SELECT ilk_giris_zorunlu FROM kullanicilar WHERE kullanici_id = ?",
            [$user['kullanici_id']]
        );
        
        echo "\n📋 Doğrulama:\n";
        echo "   İlk Giriş Zorunlu: " . ($updatedUser['ilk_giris_zorunlu'] ? 'EVET' : 'HAYIR') . "\n";
        
        // Şifre testi
        $testUser = $db->fetch(
            "SELECT sifre FROM kullanicilar WHERE kullanici_id = ?",
            [$user['kullanici_id']]
        );
        
        if (password_verify($newPassword, $testUser['sifre'])) {
            echo "   Şifre Testi: ✅ BAŞARILI\n";
        } else {
            echo "   Şifre Testi: ❌ BAŞARISIZ\n";
        }
    } else {
        echo "❌ Şifre güncellenemedi! Hiçbir satır etkilenmedi.\n";
        echo "   Bu durum şu sebeplerden olabilir:\n";
        echo "   - Kullanıcı ID'si yanlış\n";
        echo "   - Veritabanı izinleri yetersiz\n";
        echo "   - Tablo kilitli\n";
    }
} catch (Exception $e) {
    echo "❌ HATA: " . $e->getMessage() . "\n";
    exit(1);
}
