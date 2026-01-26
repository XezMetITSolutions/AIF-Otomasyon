<?php
/**
 * Toplantı PDF Raporu Oluşturma
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../vendor/autoload.php'; // TCPDF için

Middleware::requireRole([Auth::ROLE_SUPER_ADMIN, Auth::ROLE_UYE]);
$auth = new Auth();
$user = $auth->getUser();

$db = Database::getInstance();
$toplanti_id = $_GET['id'] ?? null;

if (!$toplanti_id) {
    die('Toplantı ID gereklidir');
}

// Toplantı bilgilerini getir
$toplanti = $db->fetch("
    SELECT t.*, b.byk_adi, b.byk_kodu, CONCAT(u.ad, ' ', u.soyad) as olusturan
    FROM toplantilar t
    INNER JOIN byk b ON t.byk_id = b.byk_id
    INNER JOIN kullanicilar u ON t.olusturan_id = u.kullanici_id
    WHERE t.toplanti_id = ?
", [$toplanti_id]);

if ($user['role'] === Auth::ROLE_UYE && $toplanti['byk_id'] != $user['byk_id']) {
    die('Erişim reddedildi: Bu toplantının raporunu görüntüleme yetkiniz yok.');
}

if (!$toplanti) {
    die('Toplantı bulunamadı');
}

// Katılımcıları getir
$katilimcilar = $db->fetchAll("
    SELECT 
        tk.*,
        k.ad,
        k.soyad,
        ab.alt_birim_adi
    FROM toplanti_katilimcilar tk
    INNER JOIN kullanicilar k ON tk.kullanici_id = k.kullanici_id
    LEFT JOIN alt_birimler ab ON k.alt_birim_id = ab.alt_birim_id
    WHERE tk.toplanti_id = ?
    ORDER BY tk.katilim_durumu, k.ad, k.soyad
", [$toplanti_id]);

// Gündem maddelerini getir
$gundem_maddeleri = $db->fetchAll("
    SELECT * FROM toplanti_gundem
    WHERE toplanti_id = ?
    ORDER BY sira_no
", [$toplanti_id]);

// Kararları getir
$kararlar = $db->fetchAll("
    SELECT 
        tk.*,
        tg.baslik as gundem_baslik
    FROM toplanti_kararlar tk
    LEFT JOIN toplanti_gundem tg ON tk.gundem_id = tg.gundem_id
    WHERE tk.toplanti_id = ?
    ORDER BY tk.karar_id
", [$toplanti_id]);

// Tutanağı getir
$tutanak = $db->fetch("
    SELECT * FROM toplanti_tutanak
    WHERE toplanti_id = ?
", [$toplanti_id]);

// PDF oluştur
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// PDF bilgileri
$pdf->SetCreator('Otomasyon Sistemi');
$pdf->SetAuthor($toplanti['olusturan']);
$pdf->SetTitle($toplanti['baslik']);
$pdf->SetSubject('Toplantı Raporu');

// Header ve Footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);

// Sayfa ayarları
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);

// Font
$pdf->SetFont('dejavusans', '', 10);

// Sayfa ekle
$pdf->AddPage();

// Başlık
$html = '<h1 style="text-align:center; color:#0d6efd;">' . htmlspecialchars($toplanti['baslik']) . '</h1>';
$html .= '<h3 style="text-align:center; color:#6c757d;">Toplantı Raporu</h3>';
$html .= '<hr>';

// Toplantı Bilgileri
$html .= '<h2 style="color:#0d6efd;">Toplantı Bilgileri</h2>';
$html .= '<table border="0" cellpadding="5">';
$html .= '<tr><td width="150"><strong>BYK:</strong></td><td>' . htmlspecialchars($toplanti['byk_adi']) . '</td></tr>';
$html .= '<tr><td><strong>Tarih:</strong></td><td>' . date('d.m.Y H:i', strtotime($toplanti['toplanti_tarihi'])) . '</td></tr>';
$html .= '<tr><td><strong>Konum:</strong></td><td>' . htmlspecialchars($toplanti['konum'] ?? '-') . '</td></tr>';
$html .= '</table>';
$html .= '<br>';

// Katılımcılar
$html .= '<h2 style="color:#0d6efd;">Katılımcı Durumları</h2>';

// Katılanlar
$katilacaklar = array_filter($katilimcilar, fn($k) => $k['katilim_durumu'] === 'katilacak');
if (!empty($katilacaklar)) {
    $html .= '<h3 style="color:#28a745;">Katılanlar (' . count($katilacaklar) . ')</h3>';
    $html .= '<ul>';
    foreach ($katilacaklar as $k) {
        $html .= '<li>' . htmlspecialchars($k['ad'] . ' ' . $k['soyad']) . '</li>';
    }
    $html .= '</ul>';
}

// Katılmayacaklar
$katilmayacaklar = array_filter($katilimcilar, fn($k) => $k['katilim_durumu'] === 'katilmayacak');
if (!empty($katilmayacaklar)) {
    $html .= '<h3 style="color:#dc3545;">Katılmayacaklar (' . count($katilmayacaklar) . ')</h3>';
    $html .= '<ul>';
    foreach ($katilmayacaklar as $k) {
        $html .= '<li>' . htmlspecialchars($k['ad'] . ' ' . $k['soyad']);
         if ($k['mazeret_aciklama']) {
            $html .= ' <br><small><em>Mazeret: ' . htmlspecialchars($k['mazeret_aciklama']) . '</em></small>';
        }
        $html .= '</li>';
    }
    $html .= '</ul>';
}

$html .= '<br>';

// Helper to format mentions: @Ahmet Yılmaz -> <b>Ahmet Yılmaz</b>
function formatMentions($text) {
    if (empty($text)) return '';
    // Match @Name Name (unicode supported) until punctuation or end
    // Note: Simple regex, might need refinement for complex cases
    // Explanation: @ then 1+ word characters/spaces/unicode letters, lookahead for separator
    $pattern = '/@([\w\s\p{L}]+?)(?=\s|$|<|\.|,)/u';
    $replacement = '<b>$1</b>';
    return preg_replace($pattern, $replacement, htmlspecialchars($text));
}

// ... existing code ...

// Gündem
if (!empty($gundem_maddeleri)) {
    $html .= '<h2 style="color:#0d6efd;">Gündem ve Alınan Kararlar</h2>';
    $html .= '<table border="0" cellpadding="5">';
    foreach ($gundem_maddeleri as $index => $g) {
        $html .= '<tr><td>';
        $html .= '<h3>' . ($index + 1) . '. ' . htmlspecialchars($g['baslik']) . '</h3>';
        if ($g['aciklama']) {
            $html .= '<p><em>' . nl2br(htmlspecialchars($g['aciklama'])) . '</em></p>';
        }
        if (!empty($g['gorusme_notlari'])) {
            $html .= '<div style="background-color:#f8f9fa; padding:10px; border-left: 3px solid #0d6efd;">';
            $html .= '<strong>Notlar:</strong><br>';
            // Apply formatting here instead of raw htmlspecialchars
            $html .= nl2br(formatMentions($g['gorusme_notlari']));
            $html .= '</div>';
        }
        $html .= '</td></tr>';
        $html .= '<tr><td><hr></td></tr>';
    }
    $html .= '</table>';
    $html .= '<br>';
}

// Kararlar
if (!empty($kararlar)) {
    $html .= '<h2 style="color:#0d6efd;">Alınan Kararlar</h2>';
    foreach ($kararlar as $index => $k) {
        $html .= '<h3>Karar ' . ($index + 1);
        if ($k['karar_no']) {
            $html .= ' (' . htmlspecialchars($k['karar_no']) . ')';
        }
        $html .= '</h3>';
        $html .= '<p><strong>' . htmlspecialchars($k['baslik']) . '</strong></p>';
        // Apply formatting here
        $html .= '<p>' . nl2br(formatMentions($k['karar_metni'])) . '</p>';
        
        if ($k['oylama_yapildi']) {
            $html .= '<p><strong>Oylama Sonuçları:</strong> ';
            $html .= 'Kabul: ' . $k['kabul_oyu'] . ', ';
            $html .= 'Red: ' . $k['red_oyu'] . ', ';
            $html .= 'Çekimser: ' . $k['cekinser_oyu'];
            $html .= ' - <strong>Sonuç: ' . strtoupper($k['karar_sonucu']) . '</strong></p>';
        }
        $html .= '<hr>';
    }
    $html .= '<br>';
}

// Tutanak
if ($tutanak) {
    $html .= '<h2 style="color:#0d6efd;">Tutanak</h2>';
    if ($tutanak['tutanak_no']) {
        $html .= '<p><strong>Tutanak No:</strong> ' . htmlspecialchars($tutanak['tutanak_no']) . '</p>';
    }
    $html .= '<div style="border:1px solid #dee2e6; padding:10px; background-color:#f8f9fa;">';
    $html .= nl2br(htmlspecialchars($tutanak['tutanak_metni']));
    $html .= '</div>';
}

// HTML'i PDF'e yaz
$pdf->writeHTML($html, true, false, true, false, '');

// PDF çıktısı
$filename = 'Toplanti_' . date('Y-m-d', strtotime($toplanti['toplanti_tarihi'])) . '_' . $toplanti['toplanti_id'] . '.pdf';
$pdf->Output($filename, 'I'); // I = inline görüntüleme, D = indirme
