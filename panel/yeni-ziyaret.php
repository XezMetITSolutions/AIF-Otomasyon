<?php
/**
 * Yeni Ziyaret Ekle / Raporla
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

$auth = new Auth();
$user = $auth->getUser();
if (!$user) {
    header('Location: /login.php');
    exit;
}

$db = Database::getInstance();

// AT Birimi veya Super Admin Kontrolü
$isAT = false;
$userByk = $db->fetch("SELECT b.byk_kodu FROM byk b WHERE b.byk_id = ?", [$user['byk_id']]);
if ($userByk && $userByk['byk_kodu'] === 'AT') {
    $isAT = true;
}

if (!$isAT && $user['role'] !== 'super_admin') {
    header('Location: /access-denied.php');
    exit;
}

$canManage = $auth->hasModulePermission('baskan_sube_ziyaretleri');

$editId = $_GET['edit'] ?? null;
$reportId = $_GET['rapor'] ?? null;
$ziyaret = null;
$isReport = ($reportId !== null);

if ($editId) {
    $ziyaret = $db->fetch("SELECT * FROM sube_ziyaretleri WHERE ziyaret_id = ?", [$editId]);
} elseif ($reportId) {
    $ziyaret = $db->fetch("SELECT * FROM sube_ziyaretleri WHERE ziyaret_id = ?", [$reportId]);
}

$pageTitle = $isReport ? 'Ziyaret Raporunu Doldur' : ($editId ? 'Ziyareti Düzenle' : 'Yeni Ziyaret Planla');

// Sorular Listesi
$questions = [
    1 => "Çalışma takvimi hazır mı, sisteme yüklendi mi ve aktif olarak kullanılıyor mu?",
    2 => "Haftalık veya en geç iki haftada bir ŞYK yapılıyor mu?",
    3 => "ŞYK’da eksik birim var mı, tamamlanması için çalışma yapılıyor mu?",
    4 => "Aylık GSYK (AT-KT-GT-KGT) düzenli olarak yapılıyor mu?",
    5 => "Yılda en az 1 defa (tercihen 2 defa) Üyeler Toplantısı yapılıyor mu?",
    6 => "Haftalık en az 1 lokal sohbet yapılıyor mu?",
    7 => "Teşbih sisteminde üye girişleri tam ve güncel mi?",
    8 => "Camide haftalık Kur’an-ı Kerim eğitimi durumu (öğrenci sayısı, sorunlar vb.)",
    9 => "Hafta sonu sabah namazı programı var mı?",
    10 => "Sosyal medya hesapları aktif ve düzenli kullanılıyor mu?",
    11 => "Yetişkinlere yönelik eğitim kursları var mı? Katılımcı sayısı nedir?",
    12 => "Caminin güncel sorunları nelerdir?",
    13 => "Bölge Yönetim Kurulundan beklentiler ve ihtiyaç duyulan destekler nelerdir?",
    14 => "Erkam sohbetleri düzenli olarak yapılıyor mu?",
    15 => "Esnaf ve hasta ziyaretleri yapılıyor mu?",
    16 => "Camiye gelmeyen veya küskün olabilecek cemaat için ziyaret çalışmaları yapılıyor mu?"
];

// POST İşlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canManage) {
    if (!$isReport) {
        // Planlama / Düzenleme
        $byk_id = $_POST['byk_id'];
        $grup_id = $_POST['grup_id'];
        $ziyaret_tarihi = $_POST['ziyaret_tarihi'];
        $notlar = $_POST['notlar'];
        
        if ($editId) {
            $db->query("UPDATE sube_ziyaretleri SET byk_id = ?, grup_id = ?, ziyaret_tarihi = ?, notlar = ? WHERE ziyaret_id = ?", 
                      [$byk_id, $grup_id, $ziyaret_tarihi, $notlar, $editId]);
        } else {
            $db->query("INSERT INTO sube_ziyaretleri (byk_id, grup_id, ziyaret_tarihi, notlar, olusturan_id, durum) 
                      VALUES (?, ?, ?, ?, ?, 'planlandi')", 
                      [$byk_id, $grup_id, $ziyaret_tarihi, $notlar, $user['kullanici_id']]);
        }
    } else {
        // Rapor Doldurma
        $answers = $_POST['q'] ?? [];
        $cevaplar_json = json_encode($answers, JSON_UNESCAPED_UNICODE);
        
        $db->query("UPDATE sube_ziyaretleri SET cevaplar = ?, durum = 'tamamlandi' WHERE ziyaret_id = ?", 
                  [$cevaplar_json, $reportId]);
    }
    
    $_SESSION['message'] = ['type' => 'success', 'text' => 'İşlem başarıyla tamamlandı.'];
    header('Location: sube-ziyaretleri.php');
    exit;
}

$bykList = $db->fetchAll("SELECT byk_id, byk_adi FROM byk ORDER BY byk_adi");
$gruplar = $db->fetchAll("SELECT grup_id, grup_adi FROM ziyaret_gruplari WHERE aktif = 1 ORDER BY grup_adi");

include __DIR__ . '/../includes/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --primary: #009872;
        --text-dark: #1e293b;
        --card-bg: #ffffff;
    }

    body {
        font-family: 'Inter', sans-serif;
        background: #f8fafc;
        color: var(--text-dark);
    }

    .dashboard-layout { display: flex; }
    .sidebar-wrapper { width: 250px; flex-shrink: 0; }
    .main-content { flex-grow: 1; padding: 1.5rem 2rem; max-width: 1000px; margin: 0 auto; }

    .card { border-radius: 1rem; border: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
    
    .question-row {
        background: #fff;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        border: 1px solid #e2e8f0;
        transition: border-color 0.2s;
    }
    .question-row:hover { border-color: var(--primary); }
    .question-num {
        width: 32px;
        height: 32px;
        background: var(--primary);
        color: white;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        flex-shrink: 0;
    }

    @media (max-width: 991px) {
        .dashboard-layout { display: block; }
        .sidebar-wrapper { display: none; }
    }
</style>

<div class="dashboard-layout">
    <div class="sidebar-wrapper">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    </div>

    <main class="main-content">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 fw-bold mb-1"><?php echo $pageTitle; ?></h1>
                <?php if ($isReport): ?>
                    <p class="text-muted mb-0"><strong><?php echo htmlspecialchars($ziyaret['byk_adi'] ?? ''); ?></strong> şubesi için raporu tamamlayın.</p>
                <?php endif; ?>
            </div>
            <a href="sube-ziyaretleri.php" class="btn btn-light rounded-pill px-4 no-ajax">İptal</a>
        </div>

        <form method="POST" class="needs-validation" novalidate>
            <?php if (!$isReport): ?>
                <!-- Planlama Formu -->
                <div class="card p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Ziyaret Edilecek Şube</label>
                            <select name="byk_id" class="form-select" required>
                                <option value="">Şube Seçiniz...</option>
                                <?php foreach ($bykList as $b): ?>
                                    <option value="<?php echo $b['byk_id']; ?>" <?php echo ($ziyaret && $ziyaret['byk_id'] == $b['byk_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($b['byk_adi']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Ziyaret Edecek Grup</label>
                            <select name="grup_id" class="form-select" required>
                                <option value="">Grup Seçiniz...</option>
                                <?php foreach ($gruplar as $g): ?>
                                    <option value="<?php echo $g['grup_id']; ?>" <?php echo ($ziyaret && $ziyaret['grup_id'] == $g['grup_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($g['grup_adi']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Ziyaret Tarihi</label>
                            <input type="date" name="ziyaret_tarihi" class="form-control" value="<?php echo $ziyaret ? $ziyaret['ziyaret_tarihi'] : date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Planlama Notları</label>
                            <textarea name="notlar" class="form-control" rows="3" placeholder="Ziyaretle ilgili ön notlar..."><?php echo ($ziyaret && $ziyaret['notlar'] !== null) ? htmlspecialchars($ziyaret['notlar']) : ''; ?></textarea>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 shadow-sm">
                                <?php echo $editId ? 'Güncelle' : 'Planı Kaydet'; ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Raporlama Formu -->
                <div class="alert alert-info d-flex align-items-center mb-4 rounded-4 shadow-sm border-0">
                    <i class="fas fa-info-circle fs-4 me-3"></i>
                    <div>
                        Aşağıdaki soruları ziyaret esnasında veya sonrasında objektif bir şekilde yanıtlayınız. 
                        Bu bilgiler şube performans değerlendirmesinde kullanılacaktır.
                    </div>
                </div>

                <div class="questions-container">
                    <?php foreach ($questions as $id => $text): ?>
                        <div class="question-row shadow-sm">
                            <div class="d-flex gap-3 mb-3">
                                <div class="question-num"><?php echo $id; ?></div>
                                <div class="fw-bold text-dark pt-1"><?php echo $text; ?></div>
                            </div>
                            <?php if ($id <= 7 || $id == 9 || $id == 10 || $id == 14 || $id == 15 || $id == 16): ?>
                                <!-- Evet/Hayır/Kısmen Tipi Sorular -->
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="q[<?php echo $id; ?>][status]" id="q<?php echo $id; ?>_evet" value="Evet" required>
                                    <label class="btn btn-outline-success" for="q<?php echo $id; ?>_evet">Evet</label>
                                    
                                    <input type="radio" class="btn-check" name="q[<?php echo $id; ?>][status]" id="q<?php echo $id; ?>_kismen" value="Kısmen" required>
                                    <label class="btn btn-outline-warning" for="q<?php echo $id; ?>_kismen">Kısmen</label>
                                    
                                    <input type="radio" class="btn-check" name="q[<?php echo $id; ?>][status]" id="q<?php echo $id; ?>_hayir" value="Hayır" required>
                                    <label class="btn btn-outline-danger" for="q<?php echo $id; ?>_hayir">Hayır</label>
                                </div>
                                <textarea name="q[<?php echo $id; ?>][note]" class="form-control mt-3" rows="2" placeholder="Ek açıklama (opsiyonel)..."></textarea>
                            <?php else: ?>
                                <!-- Açık Uçlu Sorular -->
                                <textarea name="q[<?php echo $id; ?>][text]" class="form-control" rows="3" required placeholder="Cevabınızı buraya yazınız..."></textarea>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="card p-4 mt-4 bg-light border">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            <i class="fas fa-exclamation-triangle me-2"></i> Raporu gönderdikten sonra düzenleme yapılamayabilir.
                        </div>
                        <button type="submit" class="btn btn-success btn-lg rounded-pill px-5 shadow-sm">
                            <i class="fas fa-paper-plane me-2"></i>Raporu Tamamla ve Gönder
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </form>
    </main>
</div>

<script>
    // Bootstrap validation
    (() => {
        'use strict'
        const forms = document.querySelectorAll('.needs-validation')
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
