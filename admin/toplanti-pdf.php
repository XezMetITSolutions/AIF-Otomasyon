<?php
/**
 * ToplantÄ± PDF Raporu OluÅŸturma
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../vendor/autoload.php'; // TCPDF iÃ§in

Middleware::requireRole([Auth::ROLE_SUPER_ADMIN, Auth::ROLE_UYE]);
$auth = new Auth();
$user = $auth->getUser();

$db = Database::getInstance();
$toplanti_id = $_GET['id'] ?? null;

if (!$toplanti_id) {
    die('ToplantÄ± ID gereklidir');
}

// ToplantÄ± bilgilerini getir
$toplanti = $db->fetch("
    SELECT t.*, b.byk_adi, b.byk_kodu, CONCAT(u.ad, ' ', u.soyad) as olusturan
    FROM toplantilar t
    INNER JOIN byk b ON t.byk_id = b.byk_id
    INNER JOIN kullanicilar u ON t.olusturan_id = u.kullanici_id
    WHERE t.toplanti_id = ?
", [$toplanti_id]);

if ($user['role'] === Auth::ROLE_UYE && $toplanti['byk_id'] != $user['byk_id']) {
    die('EriÅŸim reddedildi: Bu toplantÄ±nÄ±n raporunu gÃ¶rÃ¼ntÃ¼leme yetkiniz yok.');
}

if (!$toplanti) {
    die('ToplantÄ± bulunamadÄ±');
}

// KatÄ±lÄ±mcÄ±larÄ± getir
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

// GÃ¼ndem maddelerini getir
$gundem_maddeleri = $db->fetchAll("
    SELECT * FROM toplanti_gundem
    WHERE toplanti_id = ?
    ORDER BY sira_no
", [$toplanti_id]);

// KararlarÄ± getir
$kararlar = $db->fetchAll("
    SELECT 
        tk.*,
        tg.baslik as gundem_baslik
    FROM toplanti_kararlar tk
    LEFT JOIN toplanti_gundem tg ON tk.gundem_id = tg.gundem_id
    WHERE tk.toplanti_id = ?
    ORDER BY tk.karar_id
", [$toplanti_id]);

// TutanaÄŸÄ± getir
$tutanak = $db->fetch("
    SELECT * FROM toplanti_tutanak
    WHERE toplanti_id = ?
", [$toplanti_id]);

// PDF oluÅŸtur
// Custom PDF class for Header/Footer
class AIF_PDF extends TCPDF {
    public function Header() {
        // Logo
        
        $logoFile = __DIR__ . '/AIF.jpg'; if (file_exists($logoFile)) { $this->Image($logoFile, 110, 8, 80, 0, 'JPG'); } elseif (file_exists(__DIR__ . '/AIF.png')) { $this->Image(__DIR__ . '/AIF.png', 110, 8, 80, 0, 'PNG'); } else { $logoAssets = __DIR__ . '/../assets/img/AIF.png'; if (file_exists($logoAssets)) { $this->Image($logoAssets, 110, 8, 80, 0, 'PNG'); } }
        
        $this->SetY(40);
        $this->SetFont('dejavusans', 'B', 14);
        $this->Cell(0, 10, 'TOPLANTI TUTANAÄžI', 0, 1, 'C');
        $this->Ln(5);
    }

    public function Footer() {
        $this->SetY(-30);
        $this->SetFont('dejavusans', '', 8);
        
        // Footer Line
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->Ln(2);

        // Address and Contact (Left)
        $html_left = '<table border="0" cellpadding="1">
            <tr><td><strong>AÄ°F â€“ Avusturya Ä°slam Federasyonu</strong> | Ã–sterreichische Islamische FÃ¶deration</td></tr>
            <tr><td>Amberggasse 10 | A-6800 Feldkirch | T +43 5522 21756 | ZVR-Zahl 777051661</td></tr>
            <tr><td>info@islamfederasyonu.at | www.islamfederasyonu.at</td></tr>
        </table>';
        
        // Bank Info (Right)
        $html_right = '<table border="0" cellpadding="1" align="right">
            <tr><td><strong>Hypo Vorarlberg</strong></td></tr>
            <tr><td>IBAN: AT87 5800 0105 6645 7011</td></tr>
            <tr><td>BIC/SWIFT: HYPVATW</td></tr>
        </table>';

        $this->writeHTMLCell(120, 20, 15, $this->GetY(), $html_left, 0, 0, false, true, 'L');
        $this->writeHTMLCell(60, 20, 135, $this->GetY(), $html_right, 0, 0, false, true, 'R');
        
        // Page number
        $this->SetY(-15);
        $this->Cell(0, 10, 'Sayfa '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// TCPDF objesini yeni sÄ±nÄ±ftan oluÅŸtur
$pdf = new AIF_PDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// PDF bilgileri
$pdf->SetCreator('Otomasyon Sistemi');
$pdf->SetAuthor($toplanti['olusturan']);
$pdf->SetTitle($toplanti['baslik']);
$pdf->SetSubject('ToplantÄ± Raporu');

// Header ve Footer
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);

// Sayfa ayarlarÄ±
$pdf->SetMargins(15, 45, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(20);
$pdf->SetAutoPageBreak(TRUE, 35);

// Font
$pdf->SetFont('dejavusans', '', 10);

// Sayfa ekle
$pdf->AddPage();

// Ä°Ã§erik BaÅŸlÄ±ÄŸÄ± (Header'da olduÄŸu iÃ§in burada daha kÃ¼Ã§Ã¼k bir baÅŸlÄ±k atalÄ±m)
$html = '<h3 style="text-align:center; color:#6c757d;">' . htmlspecialchars($toplanti['baslik']) . '</h3>';
$html .= '<hr>';

// ToplantÄ± Bilgileri
$html .= '<h2 style="color:#0d6efd;">ToplantÄ± Bilgileri</h2>';
$html .= '<table border="0" cellpadding="5">';
$html .= '<tr><td width="150"><strong>BYK:</strong></td><td>' . htmlspecialchars($toplanti['byk_adi']) . '</td></tr>';
    $tarihStr = date('d.m.Y H:i', strtotime($toplanti['toplanti_tarihi']));
    if (!empty($toplanti['bitis_tarihi'])) {
        $start = new DateTime($toplanti['toplanti_tarihi']);
        $end = new DateTime($toplanti['bitis_tarihi']);
        $diff = $start->diff($end);
        
        $duration = [];
        if ($diff->h > 0) $duration[] = $diff->h . ' saat';
        if ($diff->i > 0) $duration[] = $diff->i . ' dakika';
        
        $tarihStr .= ' - ' . $end->format('H:i');
        if (!empty($duration)) {
            $tarihStr .= ' (' . implode(' ', $duration) . ')';
        }
    }
$html .= '<tr><td><strong>Tarih:</strong></td><td>' . $tarihStr . '</td></tr>';
$html .= '<tr><td><strong>Konum:</strong></td><td>' . htmlspecialchars($toplanti['konum'] ?? '-') . '</td></tr>';
$html .= '</table>';
$html .= '<br>';

// KatÄ±lÄ±mcÄ±lar
$html .= '<h2 style="color:#0d6efd;">KatÄ±lÄ±mcÄ± DurumlarÄ±</h2>';

// KatÄ±lanlar
$katilacaklar = array_filter($katilimcilar, fn($k) => $k['katilim_durumu'] === 'katilacak');
if (!empty($katilacaklar)) {
    $html .= '<h3 style="color:#28a745;">KatÄ±lanlar (' . count($katilacaklar) . ')</h3>';
    $html .= '<ul>';
    foreach ($katilacaklar as $k) {
        $html .= '<li>' . htmlspecialchars($k['ad'] . ' ' . $k['soyad']) . '</li>';
    }
    $html .= '</ul>';
}

// KatÄ±lmayacaklar
$katilmayacaklar = array_filter($katilimcilar, fn($k) => $k['katilim_durumu'] === 'katilmayacak');
if (!empty($katilmayacaklar)) {
    $html .= '<h3 style="color:#dc3545;">KatÄ±lmayacaklar (' . count($katilmayacaklar) . ')</h3>';
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

// Helper to format mentions: @Ahmet YÄ±lmaz -> <b>Ahmet YÄ±lmaz</b>
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

// GÃ¼ndem
if (!empty($gundem_maddeleri)) {
    $html .= '<h2 style="color:#0d6efd;">GÃ¼ndem ve AlÄ±nan Kararlar</h2>';
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

// DeÄŸerlendirme
if (!empty($toplanti['baskan_degerlendirmesi'])) {
    $html .= '<h2 style="color:#0d6efd;">BÃ¶lge BaÅŸkanÄ± DeÄŸerlendirmesi</h2>';
    $html .= '<div style="background-color:#f8f9fa; padding:15px; border: 1px solid #e9ecef; border-radius: 5px;">';
    $html .= nl2br(formatMentions($toplanti['baskan_degerlendirmesi']));
    $html .= '</div>';
    $html .= '<br>';
}



// HTML'i PDF'e yaz
$pdf->writeHTML($html, true, false, true, false, '');

// PDF Ã§Ä±ktÄ±sÄ±
$filename = 'Toplanti_' . date('Y-m-d', strtotime($toplanti['toplanti_tarihi'])) . '_' . $toplanti['toplanti_id'] . '.pdf';
$pdf->Output($filename, 'I'); // I = inline gÃ¶rÃ¼ntÃ¼leme, D = indirme

