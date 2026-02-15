<?php
/**
 * Başkan - Yeni Toplantı Ekleme
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';



$auth = new Auth();
$user = $auth->getUser();
session_write_close();
$db = Database::getInstance();

// Check if user belongs to 'AT' (Global Admin Unit)
$userByk = $db->fetch("SELECT * FROM byk WHERE byk_id = ?", [$user['byk_id']]);
$isAdmin = ($userByk && $userByk['byk_kodu'] === 'AT');

// BYK listesi
if ($isAdmin) {
    $bykler = $db->fetchAll("SELECT byk_id, byk_adi, byk_kodu FROM byk ORDER BY byk_adi");
} else {
    $bykler = $db->fetchAll("SELECT byk_id, byk_adi, byk_kodu FROM byk WHERE byk_id = ?", [$user['byk_id']]);
}

// Hata ve başarı mesajları
$error = '';
$success = '';

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($isAdmin) {
            $byk_id = $_POST['byk_id'] ?? $user['byk_id'];
        } else {
            $byk_id = $user['byk_id'];
        }

        $baslik = trim($_POST['baslik'] ?? '');
        $aciklama = trim($_POST['aciklama'] ?? '');
        $toplanti_tarihi = $_POST['toplanti_tarihi'] ?? '';
        $konum = trim($_POST['konum'] ?? '');
        $is_divan = isset($_POST['is_divan']) ? 1 : 0;
        $toplanti_turu = $is_divan ? 'divan' : 'normal';
        $katilimcilar = $_POST['katilimcilar'] ?? [];

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
                    $token = md5(uniqid($kullanici_id, true) . microtime());

                    try {
                        // Token ile birlikte eklemeyi dene
                        $db->query("
                            INSERT INTO toplanti_katilimcilar (
                                toplanti_id, kullanici_id, katilim_durumu, token
                            ) VALUES (?, ?, ?, ?)
                        ", [$toplanti_id, $kullanici_id, $durum, $token]);
                    } catch (Exception $e) {
                        $msg = $e->getMessage();

                        // 1. Durum: Token kolonu yoksa ekle ve tekrar dene
                        if (strpos($msg, 'Unknown column') !== false && strpos($msg, 'token') !== false) {
                            $db->query("ALTER TABLE toplanti_katilimcilar ADD COLUMN token VARCHAR(100) NULL AFTER kullanici_id");
                            $db->query("ALTER TABLE toplanti_katilimcilar ADD INDEX (token)");
                            $db->query("
                                INSERT INTO toplanti_katilimcilar (
                                    toplanti_id, kullanici_id, katilim_durumu, token
                                ) VALUES (?, ?, ?, ?)
                            ", [$toplanti_id, $kullanici_id, $durum, $token]);
                        }
                        // 2. Durum: Enum hatası (eski koddan)
                        elseif (strpos($msg, 'Data truncated') !== false || strpos($msg, '1265') !== false) {
                            $db->query("ALTER TABLE toplanti_katilimcilar MODIFY COLUMN katilim_durumu ENUM('beklemede', 'katildi', 'ozur_diledi', 'izinli', 'katilmadi') DEFAULT 'beklemede'");
                            $db->query("
                                INSERT INTO toplanti_katilimcilar (
                                    toplanti_id, kullanici_id, katilim_durumu, token
                                ) VALUES (?, ?, ?, ?)
                            ", [$toplanti_id, $kullanici_id, $durum, $token]);
                        } else {
                            throw $e;
                        }
                    }

                    // E-posta ve Bildirim Gönderimi
                    if ($kullanici_id != $user['id']) {
                        $mailSent = false;

                        // Kullanıcı bilgilerini çek
                        $receiver = $db->fetch("SELECT email, ad, soyad FROM kullanicilar WHERE kullanici_id = ?", [$kullanici_id]);

                        // Zengin Davetiye E-postası Gönder (Mail sınıfı varsa ve alıcı e-postası varsa)
                        if ($receiver && !empty($receiver['email']) && class_exists('Mail') && method_exists('Mail', 'sendMeetingInvitation')) {
                            $mailData = [
                                'ad_soyad' => $receiver['ad'] . ' ' . $receiver['soyad'],
                                'email' => $receiver['email'],
                                'baslik' => $baslik,
                                'toplanti_tarihi' => $toplanti_tarihi,
                                'konum' => $konum,
                                'aciklama' => $aciklama,
                                'token' => $token
                            ];
                            $mailSent = Mail::sendMeetingInvitation($mailData);
                        }

                        // Bildirim Ekle (Eğer mail gittiyse bildirim mailini kapat, gitmediyse aç)
                        Notification::add(
                            $kullanici_id,
                            'Toplantı Daveti',
                            "Yeni bir toplantıya davet edildiniz: $baslik",
                            'uyari',
                            '/panel/toplantilar.php',
                            !$mailSent // Mail gönderilemediyse bildirim maili gitsin
                        );
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

        // OTOMATİK GÜNDEM (Sadece AT/Birim Toplantıları için)
        $byk_info = $db->fetch("SELECT byk_kodu FROM byk WHERE byk_id = ?", [$byk_id]);

        if ($byk_info && strpos($byk_info['byk_kodu'], 'AT') !== false) {
            $standart_gundem = [
                "Blg. Bşk. Yrd. | Teşkilatlanma Bşk.",
                "Blg. Bşk. Yrd. | İrşad Bşk.",
                "Blg. Bşk. Yrd. | Eğitim Bşk.",
                "Blg. Bşk. Yrd. | Sosyal Hizmetler Bşk.",
                "Blg. Mali İşler Bşk.",
                "Blg. Sekreteri",
                "Blg. Dış Münasebetler Bşk.",
                "Blg. Teftiş Kurulu Bşk.",
                "Blg. Kurumsal İletişim Bşk.",
                "Blg. Hac - Umre ve Seyahat Bşk.",
                "Blg. UKBA Sorumlusu",
                "Blg. GT Bşk.",
                "Blg. KT Bşk.",
                "Blg. KGT Bşk.",
                "Blg. GM Üyelik Sorumlusu",
                "Blg. Tanıtma Bşk.",
                "Blg. İhsan Sohbeti Sorumlusu"
            ];

            $stmt_gundem = $db->getConnection()->prepare("
                INSERT INTO toplanti_gundem (toplanti_id, sira_no, baslik, durum) 
                VALUES (?, ?, ?, 'beklemede')
            ");

            $gsira = 1;
            foreach ($standart_gundem as $madde) {
                $stmt_gundem->execute([$toplanti_id, $gsira++, $madde]);
            }
        }

        $success = 'Toplantı başarıyla oluşturuldu!';

        // Toplantı detay sayfasına yönlendir
        header("Location: /panel/toplanti-duzenle.php?id={$toplanti_id}&success=1");
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
            <a href="/panel/toplantilar.php" class="btn btn-secondary">
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
                                    <label for="byk_id" class="form-label">BYK <span
                                            class="text-danger">*</span></label>
                                    <?php if ($isAdmin): ?>
                                        <select class="form-select" id="byk_id" name="byk_id" required
                                            onchange="loadMembers()">
                                            <?php foreach ($bykler as $byk): ?>
                                                <option value="<?php echo $byk['byk_id']; ?>" <?php echo $byk['byk_id'] == $user['byk_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($byk['byk_adi']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <input type="text" class="form-control" id="byk_display"
                                            value="<?php echo htmlspecialchars($bykler[0]['byk_adi']); ?>" readonly
                                            disabled>
                                        <input type="hidden" id="byk_id" name="byk_id"
                                            value="<?php echo $user['byk_id']; ?>">
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6 mb-3 d-flex align-items-end">
                                    <div class="form-check mb-2" id="divan_checkbox_container" style="display: none;">
                                        <input class="form-check-input" type="checkbox" id="is_divan" name="is_divan"
                                            value="1">
                                        <label class="form-check-label fw-bold" for="is_divan">
                                            <i class="fas fa-star text-warning me-1"></i>Divan Toplantısı
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="baslik" class="form-label">Toplantı Başlığı <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="baslik" name="baslik" required
                                    placeholder="Örn: Bölge Değerlendirme Toplantısı">
                            </div>

                            <div class="mb-3">
                                <label for="aciklama" class="form-label">Gündem</label>
                                <textarea class="form-control" id="aciklama" name="aciklama" rows="10"
                                    placeholder="• Gündem maddesi 1&#10;• Gündem maddesi 2"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="toplanti_tarihi" class="form-label">Tarih & Saat <span
                                            class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" id="toplanti_tarihi"
                                        name="toplanti_tarihi" required>
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
                                <div class="spinner-border spinner-border-sm" role="status"><span
                                        class="visually-hidden">Yükleniyor...</span></div>
                                <span class="ms-2">Katılımcılar yükleniyor...</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Toplantıyı Oluştur
                        </button>
                        <a href="/panel/toplantilar.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>İptal
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
    function initToplantiEkle() {
        const katilimcilarContainer = document.getElementById('katilimcilar-container');
        if (!katilimcilarContainer || katilimcilarContainer.dataset.initialized === 'true') return;
        katilimcilarContainer.dataset.initialized = 'true';

        const divanCheckboxContainer = document.getElementById('divan_checkbox_container');
        const divanCheckbox = document.getElementById('is_divan');
        const bykSelect = document.getElementById('byk_id');

        if (!bykSelect) {
            console.error('Required elements not found: byk_id');
            return;
        }

        // BYK Seçimi değiştiğinde
        if (bykSelect.tagName === 'SELECT') {
            bykSelect.onchange = function() {
                checkDivanStatus();
                loadMembers();
            };
        }

        // Sayfa yüklenince otomatik üyeleri yükle (Race condition önlemek için kısa bir gecikme)
        setTimeout(() => {
            checkDivanStatus();
            loadMembers();
        }, 50);

        function checkDivanStatus() {
            const bykSelect = document.getElementById('byk_id');
            if (!bykSelect) return;

            let bykText = '';
            if (bykSelect.tagName === 'SELECT') {
                if (bykSelect.selectedIndex > -1) {
                    bykText = bykSelect.options[bykSelect.selectedIndex].text;
                }
            } else {
                const displayEl = document.getElementById('byk_display');
                if (displayEl) {
                    bykText = displayEl.value;
                }
            }

            if (bykText.toLowerCase().includes('ana teşkilat') || bykText.toLowerCase().includes('merkez') || bykText.toLowerCase().includes('at')) {
                divanCheckboxContainer.style.display = 'block';
                // Gündemi otomatik doldur
                const gundemTextarea = document.getElementById('aciklama');
                if (gundemTextarea && (gundemTextarea.value.trim() === '' || gundemTextarea.value.includes('Blg. Bşk. Yrd.'))) {
                    gundemTextarea.value = "• Blg. Bşk. Yrd. | Teşkilatlanma Bşk.\n• Blg. Bşk. Yrd. | İrşad Bşk.\n• Blg. Bşk. Yrd. | Eğitim Bşk.\n• Blg. Bşk. Yrd. | Sosyal Hizmetler Bşk.\n• Blg. Mali İşler Bşk.\n• Blg. Sekreteri\n• Blg. Dış Münasebetler Bşk.\n• Blg. Teftiş Kurulu Bşk.\n• Blg. Kurumsal İletişim Bşk.\n• Blg. Hac - Umre ve Seyahat Bşk.\n• Blg. UKBA Sorumlusu\n• Blg. GT Bşk.\n• Blg. KT Bşk.\n• Blg. KGT Bşk.\n• Blg. GM Üyelik Sorumlusu\n• Blg. Tanıtma Bşk.\n• Blg. İhsan Sohbeti Sorumlusu";
                    gundemTextarea.rows = 18;
                }
            } else {
                divanCheckboxContainer.style.display = 'none';
                if (divanCheckbox) {
                    divanCheckbox.checked = false;
                }
                // Gündemi temizle (eğer standart gündem varsa)
                const gundemTextarea = document.getElementById('aciklama');
                if (gundemTextarea && gundemTextarea.value.includes('Blg. Bşk. Yrd.')) {
                    gundemTextarea.value = '';
                    gundemTextarea.rows = 6;
                }
            }
        }

        // Divan checkbox değiştiğinde
        if (divanCheckbox) {
            divanCheckbox.addEventListener('change', loadMembers);
        }

        window.loadMembers = loadMembers;

        function loadMembers() {
            const currentBykId = document.getElementById('byk_id').value;

            if (!currentBykId) {
                katilimcilarContainer.innerHTML = '<p class="text-muted text-center"><i class="fas fa-info-circle me-2"></i>Önce BYK seçiniz</p>';
                return;
            }

            katilimcilarContainer.innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Yükleniyor...</span></div><p class="text-muted mt-2">Katılımcılar yükleniyor...</p></div>';

            const isDivan = document.getElementById('is_divan').checked;
            fetch(`/admin/api-byk-uyeler.php?byk_id=${currentBykId}&divan_only=${isDivan ? 'true' : 'false'}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
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

                        document.querySelectorAll('input[type="checkbox"][id^="katilimci_"]').forEach(checkbox => {
                            const userId = checkbox.id.replace('katilimci_', '');
                            const select = document.getElementById(`durum_${userId}`);

                            checkbox.addEventListener('change', function () {
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
                    console.error('LoadMembers Fetch Error:', error);
                    katilimcilarContainer.innerHTML = `<p class="text-danger text-center"><i class="fas fa-exclamation-triangle me-2"></i>Katılımcılar yüklenirken hata oluştu: ${error.message}</p>`;
                });
        }

        // Gündem maddeleri için otomatik bullet point
        const gundemTextarea = document.getElementById('aciklama');
        if (gundemTextarea) {
            gundemTextarea.addEventListener('focus', function () {
                if (this.value.trim() === '') {
                    this.value = '• ';
                }
            });
            gundemTextarea.addEventListener('keydown', function (e) {
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
        }
    }

    // Initialize on both standard load and SPA page transitions
    document.addEventListener('DOMContentLoaded', initToplantiEkle);
    $(document).on('page:loaded', initToplantiEkle);

    // Immediate attempt in case of late execution or already loaded DOM
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        initToplantiEkle();
    }
</script>

<?php
include __DIR__ . '/../includes/footer.php';
?>