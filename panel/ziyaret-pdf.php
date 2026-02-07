<?php
/**
 * Ziyaret Rapor PDF Teması (Header/Footer Dahil)
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
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
    die("Ziyaret ID bulunamadı.");
}

$ziyaret = $db->fetch("
    SELECT z.*, b.byk_adi, g.grup_adi, g.renk_kodu, g.baskan_id, 
           CONCAT(u.ad, ' ', u.soyad) as olusturan,
           CONCAT(kb.ad, ' ', kb.soyad) as grup_baskani
    FROM sube_ziyaretleri z
    INNER JOIN byk b ON z.byk_id = b.byk_id
    INNER JOIN ziyaret_gruplari g ON z.grup_id = g.grup_id
    INNER JOIN kullanicilar u ON z.olusturan_id = u.kullanici_id
    LEFT JOIN kullanicilar kb ON g.baskan_id = kb.kullanici_id
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
    7 => "Tesbih sisteminde üye girişleri tam ve güncel mi?",
    8 => "Camide haftalık Kur’an-ı Kerim eğitimi durumu nedir? (öğrenci sayısı, sorunlar vb.)",
    9 => "Hafta sonu sabah namazı programı var mı?",
    10 => "Sosyal medya hesapları aktif ve düzenli kullanılıyor mu?",
    11 => "Yetişkinlere yönelik eğitim kursları var mı? Katılımcı sayısı nedir?",
    12 => "Caminin güncel sorunları nelerdir?",
    13 => "Bölge Yönetim Kurulundan beklentiler ve ihtiyaç duyulan destekler nelerdir?",
    14 => "Erkam sohbetleri düzenli olarak yapılıyor mu?",
    15 => "Esnaf ve hasta ziyaretleri yapılıyor mu?",
    16 => "Camiye gelmeyen veya küskün olabilecek cemaat için ziyaret çalışmaları yapılıyor mu?"
];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Ziyaret Raporu - <?php echo htmlspecialchars($ziyaret['byk_adi']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @media print {
            @page {
                size: A4;
                margin: 0;
            }
            body { 
                margin: 0; 
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact;
            }
            .no-print { display: none !important; }
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            margin: 0;
            padding: 0;
            color: #1e293b;
        }

        .pdf-container {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: white;
            position: relative;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }

        /* Bos AIF.html Header Styles */
        .header-wrapper {
            padding: 40px 60px;
            border-bottom: 2px solid #009872;
            margin-bottom: 30px;
            position: relative;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-logo {
            height: 60px;
        }

        .header-title {
            text-align: right;
        }

        .header-title h1 {
            color: #009872;
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }

        .header-title p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        /* Report Content Styles */
        .report-body {
            padding: 0 60px;
            flex-grow: 1;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }

        .info-item {
            font-size: 13px;
        }

        .info-label {
            color: #64748b;
            font-weight: 500;
            margin-bottom: 4px;
        }

        .info-value {
            font-weight: 600;
            color: #1e293b;
        }

        .question-item {
            margin-bottom: 20px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 15px;
            page-break-inside: avoid;
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .question-text {
            font-weight: 600;
            font-size: 14px;
            color: #1e293b;
            padding-right: 20px;
        }

        .answer-badge {
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-evet { color: #15803d; background: #f0fdf4; border: 1px solid #bbfcce; }
        .status-kismen { color: #a16207; background: #fefce8; border: 1px solid #fef08a; }
        .status-hayir { color: #be123c; background: #fff1f2; border: 1px solid #fecdd3; }

        .note-box {
            background: #f8fafc;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 13px;
            color: #475569;
            margin-top: 8px;
            border-left: 3px solid #cbd5e1;
        }

        /* Bos AIF.html Footer Styles */
        .footer-wrapper {
            padding: 30px 60px 40px;
            border-top: 1px solid #e2e8f0;
            margin-top: auto;
        }

        .footer-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
            font-size: 11px;
            color: #64748b;
            line-height: 1.6;
        }

        .footer-left b {
            color: #000;
            font-family: 'Open Sans', sans-serif;
            font-size: 12px;
        }

        .footer-divider {
            color: #00B050;
            font-weight: bold;
            margin: 0 5px;
        }

        .footer-right {
            text-align: right;
        }

        .page-break {
            page-break-after: always;
        }

        .print-btn-overlay {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 9999;
        }

        .btn-print {
            background: #009872;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0, 152, 114, 0.3);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-print:hover {
            background: #007d5e;
        }
    </style>
</head>
<body>

    <div class="print-btn-overlay no-print">
        <button onclick="window.print()" class="btn-print">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-10 0v4h8v-4m-10-4h.01"></path></svg>
            Yazdır / PDF Olarak Kaydet
        </button>
    </div>

    <div class="pdf-container">
        <!-- Header -->
        <div class="header-wrapper">
            <div class="header-content">
                <img src="/assets/img/AIF.png" alt="AIF Logo" class="header-logo" >
                <div class="header-title">
                    <h1>ŞUBE ZİYARET RAPORU</h1>
                    <p>AIF Otomasyon Sistemi</p>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="report-body">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">ŞUBE / BYK</div>
                    <div class="info-value"><?php echo htmlspecialchars($ziyaret['byk_adi']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">ZİYARET GRUBU</div>
                    <div class="info-value">
                        <span style="color: <?php echo $ziyaret['renk_kodu']; ?>">●</span> 
                        <?php echo htmlspecialchars($ziyaret['grup_adi']); ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">ZİYARET TARİHİ</div>
                    <div class="info-value"><?php echo date('d.m.Y', strtotime($ziyaret['ziyaret_tarihi'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">GRUP BAŞKANI</div>
                    <div class="info-value"><?php echo htmlspecialchars($ziyaret['grup_baskani'] ?? 'Belirtilmemiş'); ?></div>
                </div>
            </div>

            <?php foreach ($questions as $id => $text): ?>
                <div class="question-item">
                    <div class="question-header">
                        <div class="question-text"><?php echo $id; ?>. <?php echo $text; ?></div>
                        <?php if (isset($cevaplar[$id]['status'])): ?>
                            <?php 
                                $s = $cevaplar[$id]['status'];
                                $cls = ($s === 'Evet') ? 'status-evet' : (($s === 'Kısmen') ? 'status-kismen' : 'status-hayir');
                            ?>
                            <div class="answer-badge <?php echo $cls; ?>"><?php echo strtoupper($s); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (isset($cevaplar[$id]['note']) && !empty($cevaplar[$id]['note'])): ?>
                        <div class="note-box">
                            <b>Not:</b> <?php echo nl2br(htmlspecialchars($cevaplar[$id]['note'])); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($cevaplar[$id]['text']) && !empty($cevaplar[$id]['text'])): ?>
                        <div class="note-box">
                            <?php echo nl2br(htmlspecialchars($cevaplar[$id]['text'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($id == 8): // Page break after 8 questions to keep it balanced ?>
                    <!-- Optional: force clean breaks if questions are too long -->
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if (!empty($ziyaret['notlar'])): ?>
                <div style="margin-top: 30px; page-break-inside: avoid;">
                    <h3 style="font-size: 16px; border-bottom: 2px solid #009872; padding-bottom: 5px; margin-bottom: 15px;">Planlama / Ek Notlar</h3>
                    <div class="note-box" style="background: #fff; border: 1px solid #e2e8f0;">
                        <?php echo nl2br(htmlspecialchars($ziyaret['notlar'])); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer (Replicated from Bos AIF.html) -->
        <div class="footer-wrapper">
            <div class="footer-content">
                <div class="footer-left">
                    <div style="margin-bottom: 5px;">
                        <b>AİF – Avusturya İslam Federasyonu</b> <span class="footer-divider">|</span> 
                        <span style="color:#000;">Österreichische Islamische Föderation</span>
                    </div>
                    <div>
                        Amberggasse 10 <span class="footer-divider">|</span> 
                        A-6800 Feldkirch <span class="footer-divider">|</span> 
                        T +43 5522 21756 <span class="footer-divider">|</span> 
                        info@islamfederasyonu.at <span class="footer-divider">|</span> 
                        www.islamfederasyonu.at
                    </div>
                    <div style="margin-top: 3px;">
                        ZVR-Zahl 777051661
                    </div>
                </div>
                <div class="footer-right">
                    <div>Hypo Vorarlberg</div>
                    <div>IBAN: AT87 5800 0105 6645 7011</div>
                    <div>BIC/SWIFT: HYPVATW</div>
                    <div style="margin-top: 5px; font-style: italic; opacity: 0.7;">
                        Oluşturma: <?php echo date('d.m.Y H:i'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto print or preparation logic could go here
    </script>
</body>
</html>
