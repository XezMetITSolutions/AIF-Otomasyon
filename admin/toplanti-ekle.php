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
session_write_close();
$db = Database::getInstance();

$pageTitle = 'Yeni Toplantı Ekle';

// BYK listesini çek
$bykler = $db->fetchAll("SELECT byk_id, byk_adi, byk_kodu FROM byk WHERE byk_kodu IN ('AT', 'GT', 'KGT', 'KT') ORDER BY byk_adi");

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

            $success .= " Standart bölge gündem maddeleri otomatik eklendi.";
        }

        $success = 'Toplantı başarıyla oluşturuldu!' . $success;

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
                                    <label for="byk_id" class="form-label">BYK <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="byk_id" name="byk_id" required>
                                        <option value="">BYK Seçiniz...</option>
                                        <?php foreach ($bykler as $byk): ?>
                                            <option value="<?php echo $byk['byk_id']; ?>">
                                                <?php echo htmlspecialchars($byk['byk_adi']); ?>
                                                (<?php echo htmlspecialchars($byk['byk_kodu']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
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
                                    placeholder="Örn: Başkanlık Divanı Toplantısı">
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

    /**
     * Toplantı Ekleme (Admin) Başlatıcı (Ultra-Agresif)
     * SPA yapılarındaki takılmaları önlemek için polling ve retry içerir.
     */
    function initAdminToplantiEkle() {
        const container = document.getElementById('katilimcilar-container');
        if (!container || container.dataset.aggroLoaded === 'true') return;

        const bykSelect = document.getElementById('byk_id');
        if (!bykSelect) return;
        
        container.dataset.aggroLoaded = 'true';
        console.log('%c AIF Admin: Modül uyandırıldı. ', 'background: #2c3e50; color: #fff');

        const divanCheckbox = document.getElementById('is_divan');
        const divanContainer = document.getElementById('divan_checkbox_container');
        const aciklama = document.getElementById('aciklama');

        window.loadMembers = function(retry = 0) {
            const bykId = bykSelect.value;
            if (!bykId) {
                container.innerHTML = '<div class="alert alert-info py-2 small text-center">Lütfen BYK seçiniz.</div>';
                return;
            }

            if (retry === 0) {
                container.innerHTML = `
                    <div class="text-center py-5" id="admin-aggro-spinner" style="cursor:pointer">
                        <div class="spinner-border text-primary spinner-border-sm mb-2"></div>
                        <p class="text-muted small">Üye listesi hazırlanıyor...</p>
                        <small class="text-secondary">(Takılırsa buraya tıklayın)</small>
                    </div>`;
                document.getElementById('admin-aggro-spinner').onclick = () => window.loadMembers(0);
            }

            const isDivan = divanCheckbox ? divanCheckbox.checked : false;
            const apiUrl = `/admin/api-byk-uyeler.php?byk_id=${bykId}&divan_only=${isDivan}&_v=${Date.now()}`;

            fetch(apiUrl)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.uyeler) {
                        renderAdminMembers(data.uyeler);
                    } else {
                        throw new Error(data.error || 'Boş yanıt');
                    }
                })
                .catch(err => {
                    if (retry < 2) {
                        setTimeout(() => window.loadMembers(retry + 1), 1000);
                    } else {
                        container.innerHTML = '<div class="alert alert-danger py-3 small text-center">Yükleme başarısız. <a href="javascript:loadMembers(0)">Tekrar dene</a></div>';
                    }
                });
        };

        function renderAdminMembers(uyeler) {
            if (uyeler.length === 0) {
                container.innerHTML = '<div class="alert alert-light border text-center py-3 small text-muted">Üye bulunamadı.</div>';
                return;
            }

            let html = '<div class="list-group list-group-flush border rounded-3 overflow-auto" style="max-height: 400px;">';
            uyeler.forEach(uye => {
                const uid = uye.kullanici_id;
                html += `
                    <div class="list-group-item py-2 px-3 list-group-item-action">
                        <div class="form-check">
                            <input class="form-check-input admin-cb" type="checkbox" id="ka_${uid}" name="katilimcilar[${uid}]" value="beklemede" checked>
                            <label class="form-check-label w-100" for="ka_${uid}">
                                <div class="fw-bold fs-7">${uye.ad} ${uye.soyad}</div>
                                <div class="text-muted extra-small">${uye.alt_birim_adi || ''} ${uye.rol_adi || ''}</div>
                            </label>
                        </div>
                        <div class="mt-2">
                            <select class="form-select form-select-sm" name="katilimcilar[${uid}]" id="du_${uid}">
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
            container.innerHTML = html;

            container.querySelectorAll('.admin-cb').forEach(cb => {
                cb.onchange = function() {
                    const uid = this.id.substring(3);
                    const sel = document.getElementById('du_' + uid);
                    if (sel) {
                        sel.disabled = !this.checked;
                        if (!this.checked) sel.value = '';
                        else if (sel.value === '') sel.value = 'beklemede';
                    }
                };
            });
        }

        function onBykChangeAdmin() {
            const selectedOption = bykSelect.options[bykSelect.selectedIndex];
            if (!selectedOption) return;
            const bykText = selectedOption.text.toLowerCase();

            const isAT = /ana teşkilat|merkez|at|gt|kgt/i.test(bykText);
            if (divanContainer) divanContainer.style.display = isAT ? 'block' : 'none';

            if (isAT && aciklama && (aciklama.value.trim() === '' || aciklama.value.includes('Blg. Bşk. Yrd.'))) {
                aciklama.value = "• Blg. Bşk. Yrd. | Teşkilatlanma Bşk.\n• Blg. Bşk. Yrd. | İrşad Bşk.\n• Blg. Bşk. Yrd. | Eğitim Bşk.\n• Blg. Bşk. Yrd. | Sosyal Hizmetler Bşk.\n• Blg. Mali İşler Bşk.\n• Blg. Sekreteri\n• Blg. Dış Münasebetler Bşk.\n• Blg. Teftiş Kurulu Bşk.\n• Blg. Kurumsal İletişim Bşk.\n• Blg. Hac - Umre ve Seyahat Bşk.\n• Blg. UKBA Sorumlusu\n• Blg. GT Bşk.\n• Blg. KT Bşk.\n• Blg. KGT Bşk.\n• Blg. GM Üyelik Sorumlusu\n• Blg. Tanıtma Bşk.\n• Blg. İhsan Sohbeti Sorumlusu";
                aciklama.rows = 18;
            }
            window.loadMembers(0);
        }

        bykSelect.addEventListener('change', onBykChangeAdmin);
        if (divanCheckbox) divanCheckbox.addEventListener('change', () => window.loadMembers(0));

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
        
        if (bykSelect.value) onBykChangeAdmin();
    }

    (function pollAdmin() {
        initAdminToplantiEkle();
        let att = 0;
        const i = setInterval(() => {
            att++;
            const c = document.getElementById('katilimcilar-container');
            if (c && c.dataset.aggroLoaded === 'true') clearInterval(i);
            else if (att > 15) clearInterval(i);
            else initAdminToplantiEkle();
        }, 500);
    })();

    if (typeof jQuery !== 'undefined') $(document).on('page:loaded', initAdminToplantiEkle);
</script>

<?php
include __DIR__ . '/../includes/footer.php';
?>