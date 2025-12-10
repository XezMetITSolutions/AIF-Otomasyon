<?php
/**
 * Ana Yönetici - Yeni Toplantı Ekleme
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Yeni Toplantı Ekle';

// BYK listesini çek
$bykler = $db->fetchAll("SELECT byk_id, byk_adi, byk_kodu FROM byk ORDER BY byk_adi");

// Hata ve başarı mesajları
$error = '';
$success = '';

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $byk_id = $_POST['byk_id'] ?? null;
        $baslik = trim($_POST['baslik'] ?? '');
        $aciklama = trim($_POST['aciklama'] ?? '');
        $toplanti_tarihi = $_POST['toplanti_tarihi'] ?? '';
        $konum = trim($_POST['konum'] ?? '');
        $is_divan = isset($_POST['is_divan']) ? 1 : 0;
        $toplanti_turu = $is_divan ? 'divan' : 'normal';
        $katilimcilar = $_POST['katilimcilar'] ?? [];
        
        // Validasyon
        if (empty($byk_id)) {
            throw new Exception('BYK seçimi zorunludur.');
        }
        if (empty($baslik)) {
            throw new Exception('Toplantı başlığı zorunludur.');
        }
        if (empty($toplanti_tarihi)) {
            throw new Exception('Toplantı tarihi zorunludur.');
        }
        
        // Toplantıyı ekle
        $db->query("
            INSERT INTO toplantilar (
                byk_id, baslik, aciklama, toplanti_tarihi,
                konum, toplanti_turu, olusturan_id, durum, olusturma_tarihi
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'planlandi', NOW())
        ", [
            $byk_id,
            $baslik,
            $aciklama,
            $toplanti_tarihi,
            $konum,
            $toplanti_turu,
            $user['id']
        ]);
        
        $toplanti_id = $db->lastInsertId();
        
        // Katılımcıları ekle
        if (!empty($katilimcilar)) {
            foreach ($katilimcilar as $kullanici_id => $durum) {
                if (!empty($durum)) {
                    try {
                        $db->query("
                            INSERT INTO toplanti_katilimcilar (
                                toplanti_id, kullanici_id, katilim_durumu
                            ) VALUES (?, ?, ?)
                        ", [$toplanti_id, $kullanici_id, $durum]);
                    } catch (Exception $e) {
                        // Enum hatası durumunda veritabanını güncelle ve tekrar dene
                        if (strpos($e->getMessage(), 'Data truncated') !== false || strpos($e->getMessage(), '1265') !== false) {
                            $db->query("ALTER TABLE toplanti_katilimcilar MODIFY COLUMN katilim_durumu ENUM('beklemede', 'katildi', 'ozur_diledi', 'izinli', 'katilmadi') DEFAULT 'beklemede'");
                            
                            $db->query("
                                INSERT INTO toplanti_katilimcilar (
                                    toplanti_id, kullanici_id, katilim_durumu
                                ) VALUES (?, ?, ?)
                            ", [$toplanti_id, $kullanici_id, $durum]);
                        } else {
                            throw $e;
                        }
                    }
                }
            }
            
            // Katılımcı sayısını güncelle
            $katilimci_sayisi = count($katilimcilar);
            try {
                $db->query("
                    UPDATE toplantilar 
                    SET katilimci_sayisi = ? 
                    WHERE toplanti_id = ?
                ", [$katilimci_sayisi, $toplanti_id]);
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Unknown column') !== false && strpos($e->getMessage(), 'katilimci_sayisi') !== false) {
                    $db->query("ALTER TABLE toplantilar ADD COLUMN katilimci_sayisi INT DEFAULT 0 AFTER durum");
                    // Retry
                    $db->query("
                        UPDATE toplantilar 
                        SET katilimci_sayisi = ? 
                        WHERE toplanti_id = ?
                    ", [$katilimci_sayisi, $toplanti_id]);
                } else {
                    throw $e;
                }
            }
        }
        
        $success = 'Toplantı başarıyla oluşturuldu!';
        
        // Toplantı detay sayfasına yönlendir
        header("Location: /admin/toplanti-duzenle.php?id={$toplanti_id}&success=1");
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-plus-circle me-2"></i>Yeni Toplantı Ekle
            </h1>
            <a href="/admin/toplantilar.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Geri Dön
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="toplantiForm">
            <div class="row">
                <!-- Sol Kolon - Temel Bilgiler -->
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Toplantı Bilgileri</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="byk_id" class="form-label">BYK <span class="text-danger">*</span></label>
                                    <select class="form-select" id="byk_id" name="byk_id" required>
                                        <option value="">BYK Seçiniz...</option>
                                        <?php foreach ($bykler as $byk): ?>
                                            <option value="<?php echo $byk['byk_id']; ?>">
                                                <?php echo htmlspecialchars($byk['byk_adi']); ?> (<?php echo htmlspecialchars($byk['byk_kodu']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3 d-flex align-items-end">
                                    <div class="form-check mb-2" id="divan_checkbox_container" style="display: none;">
                                        <input class="form-check-input" type="checkbox" id="is_divan" name="is_divan" value="1">
                                        <label class="form-check-label fw-bold" for="is_divan">
                                            <i class="fas fa-star text-warning me-1"></i>Divan Toplantısı
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="baslik" class="form-label">Toplantı Başlığı <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="baslik" name="baslik" required 
                                       placeholder="Örn: Başkanlık Divanı Toplantısı">
                            </div>

                            <div class="mb-3">
                                <label for="aciklama" class="form-label">Gündem</label>
                                <textarea class="form-control" id="aciklama" name="aciklama" rows="6" 
                                          placeholder="• Gündem maddesi 1&#10;• Gündem maddesi 2"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="toplanti_tarihi" class="form-label">Tarih & Saat <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" id="toplanti_tarihi" name="toplanti_tarihi" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="konum" class="form-label">Konum</label>
                                <input type="text" class="form-control" id="konum" name="konum" 
                                       placeholder="Örn: Toplantı Salonu, Zoom Linki, vb.">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sağ Kolon - Katılımcılar -->
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-users me-2"></i>Katılımcılar</h5>
                        </div>
                        <div class="card-body">
                            <div id="katilimcilar-container">
                                <p class="text-muted text-center">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Önce BYK seçiniz
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Toplantıyı Oluştur
                        </button>
                        <a href="/admin/toplantilar.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>İptal
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bykSelect = document.getElementById('byk_id');
    const katilimcilarContainer = document.getElementById('katilimcilar-container');
    const divanCheckboxContainer = document.getElementById('divan_checkbox_container');
    const divanCheckbox = document.getElementById('is_divan');
    
    // BYK değiştiğinde
    bykSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const bykText = selectedOption.text.toLowerCase();
        
        // Ana Teşkilat kontrolü (İsim veya kod kontrolü)
        if (bykText.includes('ana teşkilat') || bykText.includes('merkez') || bykText.includes('AT')) {
            divanCheckboxContainer.style.display = 'block';
        } else {
            divanCheckboxContainer.style.display = 'none';
            divanCheckbox.checked = false;
        }
        
        loadMembers();
    });

    // Divan checkbox değiştiğinde
    divanCheckbox.addEventListener('change', loadMembers);

    function loadMembers() {
        const bykId = bykSelect.value;
        
        if (!bykId) {
            katilimcilarContainer.innerHTML = '<p class="text-muted text-center"><i class="fas fa-info-circle me-2"></i>Önce BYK seçiniz</p>';
            return;
        }
        
        // Loading göster
        katilimcilarContainer.innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Yükleniyor...</span></div><p class="text-muted mt-2">Katılımcılar yükleniyor...</p></div>';
        
        // AJAX ile katılımcıları getir
        const isDivan = document.getElementById('is_divan').checked;
        fetch(`/admin/api-byk-uyeler.php?byk_id=${bykId}&divan_only=${isDivan}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.uyeler.length > 0) {
                    let html = '<div class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">';
                    
                    data.uyeler.forEach(uye => {
                        html += `
                            <div class="list-group-item px-0">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           id="katilimci_${uye.kullanici_id}" 
                                           name="katilimcilar[${uye.kullanici_id}]" 
                                           value="beklemede" checked>
                                    <label class="form-check-label" for="katilimci_${uye.kullanici_id}">
                                        <strong>${uye.ad} ${uye.soyad}</strong>
                                    </label>
                                </div>
                                <select class="form-select form-select-sm" 
                                        name="katilimcilar[${uye.kullanici_id}]"
                                        id="durum_${uye.kullanici_id}">
                                    <option value="beklemede">Davet Edildi</option>
                                    <option value="katildi">Katıldı</option>
                                    <option value="ozur_diledi">Özür Diledi</option>
                                    <option value="izinli">İzinli</option>
                                    <option value="">Katılmadı</option>
                                </select>
                            </div>
                        `;
                    });
                    
                    html += '</div>';
                    html += `<div class="mt-3 text-muted small"><i class="fas fa-info-circle me-1"></i>Toplam ${data.uyeler.length} üye</div>`;
                    
                    katilimcilarContainer.innerHTML = html;
                    
                    // Checkbox ile select'i senkronize et
                    document.querySelectorAll('input[type="checkbox"][id^="katilimci_"]').forEach(checkbox => {
                        const userId = checkbox.id.replace('katilimci_', '');
                        const select = document.getElementById(`durum_${userId}`);
                        
                        checkbox.addEventListener('change', function() {
                            if (this.checked) {
                                select.disabled = false;
                                select.value = 'beklemede';
                            } else {
                                select.disabled = true;
                                select.value = '';
                            }
                        });
                    });
                    
                } else {
                    katilimcilarContainer.innerHTML = '<p class="text-muted text-center"><i class="fas fa-exclamation-circle me-2"></i>Bu BYK\'de üye bulunamadı</p>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                katilimcilarContainer.innerHTML = '<p class="text-danger text-center"><i class="fas fa-exclamation-triangle me-2"></i>Katılımcılar yüklenirken hata oluştu</p>';
            });
    }
    
    // Form validasyonu
    document.getElementById('toplantiForm').addEventListener('submit', function(e) {
        const baslik = document.getElementById('baslik').value.trim();
        const bykId = document.getElementById('byk_id').value;
        const toplanti_tarihi = document.getElementById('toplanti_tarihi').value;
        
        if (!bykId || !baslik || !toplanti_tarihi) {
            e.preventDefault();
            alert('Lütfen zorunlu alanları doldurunuz!');
            return false;
        }
    });

    // Gündem maddeleri için otomatik bullet point
    const gundemTextarea = document.getElementById('aciklama');
    
    if (gundemTextarea) {
        gundemTextarea.addEventListener('focus', function() {
            if (this.value.trim() === '') {
                this.value = '• ';
            }
        });

        gundemTextarea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const start = this.selectionStart;
                const end = this.selectionEnd;
                const value = this.value;
                const newValue = value.substring(0, start) + "\n• " + value.substring(end);
                this.value = newValue;
                this.selectionStart = this.selectionEnd = start + 3;
            }
        });
    }
});
</script>

<?php
include __DIR__ . '/../includes/footer.php';
?>
