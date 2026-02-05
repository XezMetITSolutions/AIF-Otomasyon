<?php
/**
 * Ziyaret Rapor Detayı
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
$ziyaretId = $_GET['id'] ?? null;

if (!$ziyaretId) {
    header('Location: sube-ziyaretleri.php');
    exit;
}

$ziyaret = $db->fetch("
    SELECT z.*, b.byk_adi, g.grup_adi, g.renk_kodu, CONCAT(u.ad, ' ', u.soyad) as olusturan
    FROM sube_ziyaretleri z
    INNER JOIN byk b ON z.byk_id = b.byk_id
    INNER JOIN ziyaret_gruplari g ON z.grup_id = g.grup_id
    INNER JOIN kullanicilar u ON z.olusturan_id = u.kullanici_id
    WHERE z.ziyaret_id = ?
", [$ziyaretId]);

if (!$ziyaret) {
    die("Ziyaret kaydı bulunamadı.");
}

$cevaplar = json_decode($ziyaret['cevaplar'], true) ?? [];

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

$pageTitle = 'Ziyaret Raporu - ' . $ziyaret['byk_adi'];
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

    .report-card { border-radius: 1.5rem; border: none; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); background: white; }
    
    .status-evet { color: #15803d; background: #f0fdf4; border: 1px solid #bbfcce; }
    .status-kismen { color: #a16207; background: #fefce8; border: 1px solid #fef08a; }
    .status-hayir { color: #be123c; background: #fff1f2; border: 1px solid #fecdd3; }
    
    .badge-status {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .question-box {
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .question-box:last-child { border-bottom: none; }

    @media print {
        .sidebar-wrapper, .btn-print-hide { display: none !important; }
        .main-content { padding: 0 !important; max-width: 100% !important; }
        .report-card { box-shadow: none !important; border: 1px solid #eee !important; }
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
        <div class="mb-4 d-flex justify-content-between align-items-center btn-print-hide">
            <div>
                <h1 class="h4 fw-bold mb-1"><i class="fas fa-file-invoice me-2 text-primary"></i>Ziyaret Raporu</h1>
                <p class="text-muted mb-0">Rapor Detayları ve Değerlendirme</p>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-outline-dark rounded-pill px-4">
                    <i class="fas fa-print me-2"></i>Yazdır / PDF
                </button>
                <a href="sube-ziyaretleri.php" class="btn btn-light rounded-pill px-4 no-ajax">Geri Dön</a>
            </div>
        </div>

        <div class="report-card overflow-hidden">
            <div class="p-4 p-md-5 border-bottom bg-light">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <h2 class="fw-bold text-dark mb-3"><?php echo htmlspecialchars($ziyaret['byk_adi']); ?></h2>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="text-muted small">
                                <i class="fas fa-users-rectangle me-1"></i> Ziyaret Grubu: 
                                <span class="badge" style="background-color: <?php echo $ziyaret['renk_kodu']; ?>20; color: <?php echo $ziyaret['renk_kodu']; ?>; border: 1px solid <?php echo $ziyaret['renk_kodu']; ?>40;">
                                    <?php echo htmlspecialchars($ziyaret['grup_adi']); ?>
                                </span>
                            </div>
                            <div class="text-muted small">
                                <i class="fas fa-calendar-check me-1"></i> Tarih: <strong><?php echo date('d.m.Y', strtotime($ziyaret['ziyaret_tarihi'])); ?></strong>
                            </div>
                            <div class="text-muted small">
                                <i class="fas fa-user-edit me-1"></i> Raporlayan: <strong><?php echo htmlspecialchars($ziyaret['olusturan']); ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5 text-md-end mt-3 mt-md-0">
                        <div class="badge-status <?php echo $ziyaret['durum'] === 'tamamlandi' ? 'status-evet' : 'status-kismen'; ?> px-4 py-3">
                            <i class="fas <?php echo $ziyaret['durum'] === 'tamamlandi' ? 'fa-check-double' : 'fa-clock'; ?> me-2"></i>
                            <?php echo $ziyaret['durum'] === 'tamamlandi' ? 'TAMAMLANMIŞ RAPOR' : 'PLANLANMIŞ ZİYARET'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-4 p-md-5">
                <h5 class="fw-bold mb-4 border-start border-primary border-4 ps-3">Değerlendirme Soruları ve Yanıtlar</h5>
                
                <?php foreach ($questions as $id => $text): ?>
                    <div class="question-box">
                        <div class="row align-items-start">
                            <div class="col-md-8">
                                <div class="small text-muted mb-1">Soru <?php echo $id; ?></div>
                                <div class="fw-bold text-dark mb-2"><?php echo $text; ?></div>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <?php if (isset($cevaplar[$id]['status'])): ?>
                                    <?php 
                                        $s = $cevaplar[$id]['status'];
                                        $cls = ($s === 'Evet') ? 'status-evet' : (($s === 'Kısmen') ? 'status-kismen' : 'status-hayir');
                                    ?>
                                    <span class="badge-status <?php echo $cls; ?> small">
                                        <?php echo $s; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (isset($cevaplar[$id]['note']) && !empty($cevaplar[$id]['note'])): ?>
                            <div class="mt-2 p-3 bg-light rounded-3 small">
                                <i class="fas fa-comment-dots text-muted me-2"></i>
                                <?php echo nl2br(htmlspecialchars($cevaplar[$id]['note'])); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($cevaplar[$id]['text']) && !empty($cevaplar[$id]['text'])): ?>
                            <div class="mt-2 p-3 bg-light rounded-3">
                                <?php echo nl2br(htmlspecialchars($cevaplar[$id]['text'])); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!isset($cevaplar[$id])): ?>
                            <div class="mt-2 text-muted small italic">Yanıt verilmemiş.</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <?php if ($ziyaret['notlar']): ?>
                    <div class="mt-5 p-4 border rounded-4 bg-light">
                        <h6 class="fw-bold mb-3"><i class="fas fa-sticky-note me-2 text-primary"></i>Planlama / Ek Notlar</h6>
                        <div class="text-dark"><?php echo nl2br(htmlspecialchars($ziyaret['notlar'])); ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="p-4 bg-light text-center text-muted small border-top">
                Bu rapor AIF Otomasyon Sistemi tarafından <strong><?php echo date('d.m.Y H:i'); ?></strong> tarihinde oluşturulmuştur.
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
