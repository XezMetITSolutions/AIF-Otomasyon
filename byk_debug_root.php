<?php
// Root klasörde BYK Debug Sayfası - Session kontrolü YOK
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>BYK Debug Sayfası - Root Klasör</h1>";
echo "<p>Tarih: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>PHP Versiyonu: " . phpversion() . "</p>";

// Dosya varlığını kontrol et
$files = [
    'admin/includes/database.php',
    'admin/includes/byk_manager_db.php'
];

echo "<h2>Dosya Kontrolü:</h2>";
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file var<br>";
    } else {
        echo "❌ $file YOK<br>";
    }
}

// BYK test
echo "<h2>BYK Test:</h2>";
try {
    require_once 'admin/includes/database.php';
    echo "✅ database.php yüklendi<br>";
    
    require_once 'admin/includes/byk_manager_db.php';
    echo "✅ byk_manager_db.php yüklendi<br>";
    
    $bykCategories = BYKManager::getBYKCategories();
    echo "✅ BYK kategorileri: " . count($bykCategories) . " adet<br>";
    
    echo "<h3>BYK Kategorileri:</h3>";
    foreach ($bykCategories as $byk) {
        echo "&nbsp;&nbsp;• " . $byk['code'] . " - " . $byk['name'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "<br>";
}

// Test formu
echo "<h2>BYK Test Formu:</h2>";
echo '<form id="bykTestForm">';
echo '<input type="text" name="first_name" placeholder="Ad" value="Debug" required><br><br>';
echo '<input type="text" name="last_name" placeholder="Soyad" value="Test" required><br><br>';
echo '<input type="email" name="email" placeholder="E-posta" value="debug.test@aif.com" required><br><br>';
echo '<input type="text" name="username" placeholder="Kullanıcı Adı" value="debug.test" required><br><br>';
echo '<select name="byk" required>';
echo '<option value="">BYK Seçin</option>';
if (isset($bykCategories)) {
    foreach ($bykCategories as $byk) {
        echo '<option value="' . $byk['code'] . '">' . $byk['code'] . ' - ' . $byk['name'] . '</option>';
    }
}
echo '</select><br><br>';
echo '<select name="role" required>';
echo '<option value="manager">Manager</option>';
echo '<option value="member">Üye</option>';
echo '</select><br><br>';
echo '<select name="status" required>';
echo '<option value="active">Aktif</option>';
echo '<option value="inactive">Pasif</option>';
echo '</select><br><br>';
echo '<button type="button" onclick="testBYK()">Test Kullanıcı Ekle</button>';
echo '</form>';

echo '<div style="margin-top: 20px;">';
echo '<button type="button" onclick="checkUsers()" style="background: #28a745; color: white; padding: 10px; border: none; border-radius: 5px;">Kullanıcıları Kontrol Et</button>';
echo '<button type="button" onclick="testPasswordChange()" style="background: #ffc107; color: black; padding: 10px; border: none; border-radius: 5px; margin-left: 10px;">Şifre Değiştirme Test</button>';
echo '</div>';

echo '<div id="result" style="margin-top: 20px; padding: 10px; border: 1px solid #ccc; background: #f9f9f9;"></div>';

echo "<br><p>Test tamamlandı!</p>";
?>

<script>
function testBYK() {
    const form = document.getElementById('bykTestForm');
    const formData = new FormData(form);
    const userData = Object.fromEntries(formData);
    
    // Otomatik username oluştur (eğer boşsa)
    if (!userData.username || userData.username === '') {
        const firstName = userData.first_name.toLowerCase();
        const lastName = userData.last_name.toLowerCase();
        userData.username = firstName + '.' + lastName;
    }
    
    const resultDiv = document.getElementById('result');
    resultDiv.innerHTML = '<h3>Test Başladı...</h3>';
    resultDiv.innerHTML += '<p>Form verisi: ' + JSON.stringify(userData, null, 2) + '</p>';
    
    // BYK alanını düzelt (users.php'deki gibi)
    if (userData.byk_category) {
        userData.byk = userData.byk_category;
        delete userData.byk_category;
    }
    
    resultDiv.innerHTML += '<p>Düzeltilmiş veri: ' + JSON.stringify(userData, null, 2) + '</p>';
    
    fetch('admin/add_user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(userData)
    })
    .then(response => {
        resultDiv.innerHTML += '<p>Response status: ' + response.status + '</p>';
        return response.json();
    })
    .then(data => {
        resultDiv.innerHTML += '<h3>Response Data:</h3>';
        resultDiv.innerHTML += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        
        if (data.success) {
            resultDiv.innerHTML += '<p style="color: green;">✅ Kullanıcı başarıyla eklendi!</p>';
            if (data.debug) {
                resultDiv.innerHTML += '<p style="color: blue;">🔍 Debug Info: BYK=' + data.debug.byk_input + ', ID=' + data.debug.byk_category_id + '</p>';
                resultDiv.innerHTML += '<p style="color: blue;">🔍 BYK Found: ' + data.debug.byk_category_found + '</p>';
            }
        } else {
            resultDiv.innerHTML += '<p style="color: red;">❌ Hata: ' + data.message + '</p>';
            if (data.debug) {
                resultDiv.innerHTML += '<p style="color: orange;">🔍 Debug Error: BYK=' + data.debug.byk_input + ', ID=' + data.debug.byk_category_id + '</p>';
            }
        }
    })
    .catch(error => {
        resultDiv.innerHTML += '<p style="color: red;">❌ Fetch hatası: ' + error.message + '</p>';
    });
}

function checkUsers() {
    const resultDiv = document.getElementById('result');
    resultDiv.innerHTML = '<h3>Kullanıcılar Kontrol Ediliyor...</h3>';
    
    fetch('admin/get_user.php?action=list')
        .then(response => response.json())
        .then(data => {
            resultDiv.innerHTML += '<h3>Kullanıcı Listesi:</h3>';
            resultDiv.innerHTML += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            
            if (data.success && data.users) {
                resultDiv.innerHTML += '<h4>BYK Bilgileri:</h4>';
                data.users.forEach(user => {
                    if (user.username === 'debug.test') {
                        resultDiv.innerHTML += '<p><strong>Debug Test Kullanıcısı:</strong></p>';
                        resultDiv.innerHTML += '<p>Username: ' + user.username + '</p>';
                        resultDiv.innerHTML += '<p>BYK Category ID: ' + (user.byk_category_id || 'NULL') + '</p>';
                        resultDiv.innerHTML += '<p>BYK Name: ' + (user.byk_name || 'NULL') + '</p>';
                        resultDiv.innerHTML += '<p>BYK Code: ' + (user.byk_code || 'NULL') + '</p>';
                    }
                });
            }
        })
        .catch(error => {
            resultDiv.innerHTML += '<p style="color: red;">❌ Kullanıcı kontrolü hatası: ' + error.message + '</p>';
        });
}

function testPasswordChange() {
    const resultDiv = document.getElementById('result');
    resultDiv.innerHTML = '<h3>Şifre Değiştirme Test Başladı...</h3>';
    
    const passwordData = {
        username: 'debug.test',
        password: 'Test123456'
    };
    
    resultDiv.innerHTML += '<p>Test verisi: ' + JSON.stringify(passwordData, null, 2) + '</p>';
    
    fetch('admin/change_password.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(passwordData)
    })
    .then(response => {
        resultDiv.innerHTML += '<p>Response status: ' + response.status + '</p>';
        return response.text(); // JSON yerine text al
    })
    .then(data => {
        resultDiv.innerHTML += '<h3>Response Data (Raw):</h3>';
        resultDiv.innerHTML += '<pre>' + data + '</pre>';
        
        try {
            const jsonData = JSON.parse(data);
            resultDiv.innerHTML += '<h3>Response Data (Parsed):</h3>';
            resultDiv.innerHTML += '<pre>' + JSON.stringify(jsonData, null, 2) + '</pre>';
            
            if (jsonData.success) {
                resultDiv.innerHTML += '<p style="color: green;">✅ Şifre başarıyla değiştirildi!</p>';
            } else {
                resultDiv.innerHTML += '<p style="color: red;">❌ Hata: ' + jsonData.message + '</p>';
            }
        } catch (e) {
            resultDiv.innerHTML += '<p style="color: red;">❌ JSON Parse Hatası: ' + e.message + '</p>';
        }
    })
    .catch(error => {
        resultDiv.innerHTML += '<p style="color: red;">❌ Fetch hatası: ' + error.message + '</p>';
    });
}
</script>
