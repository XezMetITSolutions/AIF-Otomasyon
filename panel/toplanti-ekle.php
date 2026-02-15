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

// Check if user is Super Admin for multi-BYK access
$isAdmin = $auth->isSuperAdmin();

// BYK listesi
if ($isAdmin) {
    $bykler = $db->fetchAll("SELECT byk_id, byk_adi, byk_kodu FROM byk WHERE byk_kodu IN ('AT', 'GT', 'KGT', 'KT') ORDER BY byk_adi");
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
    /**
     * Toplantı Ekleme Sayfası Başlatıcı (Ultra-Agresif Sürüm)
     * SPA sistemlerindeki "Loading" takılmalarını çözmek için 
     * watchdog ve recursive-retry mekanizması eklenmiştir.
     */
    function initToplantiEkle() {
        const container = document.getElementById('katilimcilar-container');
        if (!container) return;

        // Kontrol bayrağı
        if (container.dataset.loadedOnce === 'true') return;
        
        console.log('%c AIF: Toplantı Ekle modülü uyandırıldı. ', 'background: #009872; color: #fff');

        const bykSelect = document.getElementById('byk_id');
        const divanCheckbox = document.getElementById('is_divan');
        const aciklama = document.getElementById('aciklama');

        if (!bykSelect) {
            console.warn('AIF: "byk_id" henüz DOM\'da yok, bekleniyor...');
            setTimeout(initToplantiEkle, 200);
            return;
        }

        container.dataset.loadedOnce = 'true';

        /**
         * API'den üyeleri çeker.
         * @param {number} retry Count for recursive retry
         */
        window.loadMembers = function(retry = 0) {
            const bykId = bykSelect.value;
            
            if (!bykId) {
                container.innerHTML = '<div class="alert alert-info py-2 small text-center">BYK seçimi bekleniyor...</div>';
                return;
            }

            // Durumu güncelle
            if (retry === 0) {
                container.innerHTML = `
                    <div class="text-center py-5" id="aggro-spinner">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-2">Katılımcı listesi yükleniyor...</p>
                        <small class="text-secondary">(İşlem uzun sürerse buraya tıklayın)</small>
                    </div>
                `;
                document.getElementById('aggro-spinner').onclick = () => window.loadMembers(0);
            }

            const isDivan = divanCheckbox ? divanCheckbox.checked : false;
            const ts = new Date().getTime();
            const url = `/admin/api-byk-uyeler.php?byk_id=${bykId}&divan_only=${isDivan}&_v=${ts}`;

            console.log(`AIF: Fetch başlatılıyor (Dene: ${retry + 1}): ${url}`);

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 8000); // 8 saniye timeout

            fetch(url, { signal: controller.signal })
                .then(res => {
                    clearTimeout(timeoutId);
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(data => {
                    console.log('AIF: API Yanıtı:', data);
                    if (data.success && data.uyeler) {
                        displayMembers(data.uyeler);
                    } else {
                        throw new Error(data.error || 'Veri alınamadı');
                    }
                })
                .catch(err => {
                    console.error('AIF: Yükleme Hatası:', err);
                    if (retry < 2) { // 2 kez otomatik tekrar dene
                        console.log('AIF: Tekrar deneniyor...');
                        setTimeout(() => window.loadMembers(retry + 1), 1000);
                    } else {
                        container.innerHTML = `
                            <div class="alert alert-danger p-3 text-center">
                                <h6 class="alert-heading">Liste Yüklenemedi</h6>
                                <p class="small">${err.message === 'AbortError' ? 'Zaman aşımı (Timeout)' : err.message}</p>
                                <button type="button" class="btn btn-sm btn-danger mt-2" onclick="loadMembers(0)">
                                    <i class="fas fa-sync me-1"></i>Şimdi Yeniden Dene
                                </button>
                            </div>
                        `;
                    }
                });
        };

        function displayMembers(uyeler) {
            if (uyeler.length === 0) {
                container.innerHTML = '<div class="alert alert-light border text-center py-3">Bu kategoride aktif üye bulunamadı.</div>';
                return;
            }

            let html = '<div class="list-group list-group-flush border rounded-3 overflow-auto shadow-sm" style="max-height: 400px;">';
            uyeler.forEach(uye => {
                const uid = uye.kullanici_id;
                html += `
                    <div class="list-group-item py-2 px-3">
                        <div class="form-check">
                            <input class="form-check-input member-trigger" type="checkbox" id="k_${uid}" name="katilimcilar[${uid}]" value="beklemede" checked>
                            <label class="form-check-label w-100" for="k_${uid}">
                                <div class="fw-bold">${uye.ad} ${uye.soyad}</div>
                                <div class="text-muted extra-small">${uye.alt_birim_adi || ''} | ${uye.rol_adi || ''}</div>
                            </label>
                        </div>
                        <div class="mt-2">
                            <select class="form-select form-select-sm bg-light" name="katilimcilar[${uid}]" id="d_${uid}">
                                <option value="beklemede">Davet Edildi</option>
                                <option value="katildi">Katıldı</option>
                                <option value="ozur_diledi">Özür Diledi</option>
                                <option value="izinli">İzinli</option>
                                <option value="">Katılmadı</option>
                            </select>
                        </div>
                    </div>`;
            });
            html += '</div>';
            html += `<div class="mt-2 text-end small text-muted"><i class="fas fa-user-check me-1"></i>${uyeler.length} Kişi</div>`;
            
            container.innerHTML = html;

            container.querySelectorAll('.member-trigger').forEach(cb => {
                cb.onchange = function() {
                    const uid = this.id.substring(2);
                    const sel = document.getElementById('d_' + uid);
                    if (sel) {
                        sel.disabled = !this.checked;
                        if (!this.checked) sel.value = '';
                        else if (sel.value === '') sel.value = 'beklemede';
                    }
                };
            });
        }

        function checkGundemAutomation() {
            let bykText = '';
            if (bykSelect.tagName === 'SELECT') {
                bykText = bykSelect.options[bykSelect.selectedIndex]?.text || '';
            } else {
                bykText = document.getElementById('byk_display')?.value || '';
            }

            const isAT = /ana teşkilat|merkez|at|gt|kgt/i.test(bykText);
            const divanCont = document.getElementById('divan_checkbox_container');
            if (divanCont) divanCont.style.display = isAT ? 'block' : 'none';

            if (isAT && aciklama && (aciklama.value.trim() === '' || aciklama.value.includes('Blg. Bşk. Yrd.'))) {
                aciklama.value = "• Blg. Bşk. Yrd. | Teşkilatlanma Bşk.\n• Blg. Bşk. Yrd. | İrşad Bşk.\n• Blg. Bşk. Yrd. | Eğitim Bşk.\n• Blg. Bşk. Yrd. | Sosyal Hizmetler Bşk.\n• Blg. Mali İşler Bşk.\n• Blg. Sekreteri\n• Blg. Dış Münasebetler Bşk.\n• Blg. Teftiş Kurulu Bşk.\n• Blg. Kurumsal İletişim Bşk.\n• Blg. Hac - Umre ve Seyahat Bşk.\n• Blg. UKBA Sorumlusu\n• Blg. GT Bşk.\n• Blg. KT Bşk.\n• Blg. KGT Bşk.\n• Blg. GM Üyelik Sorumlusu\n• Blg. Tanıtma Bşk.\n• Blg. İhsan Sohbeti Sorumlusu";
                aciklama.rows = 18;
            }
        }

        bykSelect.onchange = () => { checkGundemAutomation(); window.loadMembers(0); };
        if (divanCheckbox) divanCheckbox.onchange = () => window.loadMembers(0);

        if (aciklama) {
            aciklama.addEventListener('focus', function() { if (this.value.trim() === '') this.value = '• '; });
            aciklama.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const start = this.selectionStart;
                    this.value = this.value.substring(0, start) + "\n• " + this.value.substring(this.selectionEnd);
                    this.selectionStart = this.selectionEnd = start + 3;
                }
            });
        }

        // İlk Çalıştırma
        checkGundemAutomation();
        window.loadMembers(0);
    }

    // SPA-Ready Initialization
    (function aggroInit() {
        initToplantiEkle();
        // Eğer başarılı olmadıysa periyodik kontrol (Race condition savar)
        let attempt = 0;
        const interval = setInterval(() => {
            attempt++;
            const container = document.getElementById('katilimcilar-container');
            if (container && container.dataset.loadedOnce === 'true') {
                clearInterval(interval);
            } else if (attempt > 15) {
                clearInterval(interval);
            } else {
                initToplantiEkle();
            }
        }, 500);
    })();

    if (typeof jQuery !== 'undefined') {
        $(document).on('page:loaded', initToplantiEkle);
    }
</script>

<?php
include __DIR__ . '/../includes/footer.php';
?>