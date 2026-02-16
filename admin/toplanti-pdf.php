<?php
/**
 * Toplantı PDF Raporu Oluşturma
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../vendor/autoload.php';

$auth = new Auth();
if (!$auth->checkAuth()) {
    die('Oturum açmanız gerekmektedir.');
}
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

if (!$toplanti) {
    die('Toplantı bulunamadı');
}

if ($user['role'] === Auth::ROLE_UYE && $toplanti['byk_id'] != $user['byk_id']) {
    die('Erişim reddedildi: Bu toplantının raporunu görüntüleme yetkiniz yok.');
}

// Katılımcıları getir
$katilimcilar = $db->fetchAll("
    SELECT tk.*, k.ad, k.soyad, ab.alt_birim_adi
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
    SELECT tk.*, tg.baslik as gundem_baslik
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

/**
 * Helpler to format mentions: @Ahmet Yılmaz -> Ahmet Yılmaz
 */
function formatMentions($text) {
    if (empty($text)) return '';
    // Match @Name Name (unicode supported) until punctuation or end
    $pattern = '/@([\w\s\p{L}]+?)(?=\s|$|<|\.|,)/u';
    $replacement = '$1';
    return preg_replace($pattern, $replacement, htmlspecialchars($text));
}

// PDF oluştur
class AIF_PDF extends TCPDF {
    public function Header() {
        // Logo
        $logoFile = __DIR__ . '/AIF.png';
        if (file_exists($logoFile)) {
            $this->Image($logoFile, 150, 8, 40, 0, 'PNG');
        } else {
            $logoAssets = __DIR__ . '/../assets/img/AIF.png';
            if (file_exists($logoAssets)) {
                $this->Image($logoAssets, 150, 8, 40, 0, 'PNG');
            }
        }
        
        $this->SetY(40);
        // $this->SetFont('dejavusans', 'B', 14);
        // $this->Cell(0, 10, 'TOPLANTI TUTANAĞI', 0, 1, 'C');
        $this->Ln(5);
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
            <tr><td><strong>AİF – Avusturya İslam Federasyonu</strong> | Österreichische Islamische Föderation</td></tr>
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

// TCPDF objesini yeni sınıftan oluştur
$pdf = new AIF_PDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// PDF bilgileri
$pdf->SetCreator('Otomasyon Sistemi');
$pdf->SetAuthor($toplanti['olusturan']);
$pdf->SetTitle($toplanti['baslik']);
$pdf->SetSubject('Toplantı Raporu');

// Header ve Footer
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);

// Sayfa ayarları
$pdf->SetMargins(15, 45, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(20);
$pdf->SetAutoPageBreak(TRUE, 35);

// Font
$pdf->SetFont('dejavusans', '', 10);

// Sayfa ekle
$pdf->AddPage();

// İçerik Başlığı
$html = '<h3 style="text-align:center; color:#6c757d;">' . htmlspecialchars($toplanti['baslik']) . '</h3>';
$html .= '<hr>';

// Toplantı Bilgileri
$html .= '<h2 style="color:#0d6efd;">Toplantı Bilgileri</h2>';
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

// Katılımcılar
$html .= '<h2 style="color:#0d6efd;">Katılımcı Durumları</h2>';

// Katılanlar
$katilacaklar = array_filter($katilimcilar, fn($k) => $k['katilim_durumu'] === 'katilacak');
if (!empty($katilacaklar)) {
    $html .= '<h4>Katılanlar (' . count($katilacaklar) . ')</h4>';
    $html .= '<p>';
    $names = array_map(fn($k) => htmlspecialchars($k['ad'] . ' ' . $k['soyad']), $katilacaklar);
    $html .= implode(', ', $names);
    $html .= '</p>';
}

// Katılmayacaklar
$katilmayacaklar = array_filter($katilimcilar, fn($k) => $k['katilim_durumu'] === 'katilmayacak');
if (!empty($katilmayacaklar)) {
    $html .= '<h4>Katılmayacaklar (' . count($katilmayacaklar) . ')</h4>';
    $html .= '<table border="0" cellpadding="3">';
    foreach ($katilmayacaklar as $k) {
        $mazeret = !empty($k['mazeret_aciklama']) ? ' (Mazeret: ' . htmlspecialchars($k['mazeret_aciklama']) . ')' : '';
        $html .= '<tr><td>• ' . htmlspecialchars($k['ad'] . ' ' . $k['soyad']) . $mazeret . '</td></tr>';
    }
    $html .= '</table>';
}

$html .= '<br>';

// Gündem ve Alınan Kararlar
if (!empty($gundem_maddeleri)) {
    $html .= '<h2 style="color:#0d6efd;">Gündem ve Alınan Kararlar</h2>';
    
    foreach ($gundem_maddeleri as $index => $g) {
        $html .= '<div style="background-color: #f8f9fa; padding: 10px; border-left: 4px solid #0d6efd; margin-bottom: 15px;">';
        $html .= '<h3>' . ($index + 1) . '. ' . htmlspecialchars($g['baslik']) . '</h3>';
        
        if ($g['aciklama']) {
            $html .= '<p><i>' . nl2br(htmlspecialchars($g['aciklama'])) . '</i></p>';
        }
        
        if (!empty($g['gorusme_notlari'])) {
            $html .= '<div style="margin-top: 10px; padding: 10px; border-top: 1px solid #dee2e6;">';
            $html .= '<strong>Notlar:</strong><br>';
            $html .= nl2br(formatMentions($g['gorusme_notlari']));
            $html .= '</div>';
        }
        
        // Bu gündem maddesine bağlı kararları bul
        $ilgili_kararlar = array_filter($kararlar, fn($k) => $k['gundem_id'] == $g['gundem_id']);
        if (!empty($ilgili_kararlar)) {
            $html .= '<div style="margin-top: 10px; background-color: #e7f1ff; padding: 10px; border-radius: 5px;">';
            $html .= '<strong>Karar:</strong><br>';
            foreach ($ilgili_kararlar as $k) {
                $html .= nl2br(formatMentions($k['karar_notu']));
            }
            $html .= '</div>';
        }
        $html .= '</div><br>';
    }
}

// Genel Değerlendirme
if (!empty($toplanti['baskan_degerlendirmesi'])) {
    $html .= '<h2 style="color:#0d6efd;">Bölge Başkanı Değerlendirmesi</h2>';
    $html .= '<div style="background-color: #e9ecef; padding: 15px; border-radius: 5px;">';
    $html .= nl2br(formatMentions($toplanti['baskan_degerlendirmesi']));
    $html .= '</div>';
}

// HTML'i PDF'e yaz
$pdf->writeHTML($html, true, false, true, false, '');

// PDF çıktısı
$filename = 'Toplanti_' . date('Y-m-d', strtotime($toplanti['toplanti_tarihi'])) . '_' . $toplanti['toplanti_id'] . '.pdf';
$pdf->Output($filename, 'I');
