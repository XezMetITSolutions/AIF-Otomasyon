<?php
/**
 * İstişare Oylaması PDF Çıktısı
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/MeetingPDF.php'; // AIF_PDF sınıfı için

// Sadece adminler veya yetkililer
Middleware::requireRole(['super_admin', 'uye']);

$db = Database::getInstance();

$votes = $db->fetchAll("
    SELECT t1.secilen_1, t1.secilen_2, t1.secilen_3, t1.secilen_4, t1.secilen_5 
    FROM istisare_oylama t1
    INNER JOIN (
        SELECT voter_id, MAX(id) AS latest_id
        FROM istisare_oylama
        GROUP BY voter_id
    ) t2 ON t1.id = t2.latest_id
");

$stats = [];
foreach ($votes as $v) {
    for($i=1; $i<=5; $i++) {
        $name = trim($v['secilen_'.$i]);
        if (!empty($name)) {
            if (!isset($stats[$name])) {
                $stats[$name] = [
                    'total' => 0,
                    'score' => 0,
                    'ranks' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0]
                ];
            }
            $stats[$name]['total']++;
            $stats[$name]['ranks'][$i]++;
            
            // Puanlamaya göre ağırlıklı skor: 1: 1, 2: 0.5, 3: 0.33, 4: 0.25, 5: 0.20
            if ($i == 1) $stats[$name]['score'] += 1;
            elseif ($i == 2) $stats[$name]['score'] += 0.50;
            elseif ($i == 3) $stats[$name]['score'] += 0.33;
            elseif ($i == 4) $stats[$name]['score'] += 0.25;
            elseif ($i == 5) $stats[$name]['score'] += 0.20;
        }
    }
}

// Ağırlıklı puana göre sırala
uasort($stats, function($a, $b) {
    if (abs($a['score'] - $b['score']) < 0.001) {
        if ($a['total'] == $b['total']) return 0;
        return ($a['total'] < $b['total']) ? 1 : -1;
    }
    return ($a['score'] < $b['score']) ? 1 : -1;
});

// PDF oluştur
$pdf = new AIF_PDF('P', 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetCreator('AIF Otomasyon');
$pdf->SetAuthor('Sistem');
$pdf->SetTitle('Başkanlık İstişare Sonuçları');
$pdf->SetSubject('İstişare Raporu');

$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);

$pdf->SetMargins(15, 30, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(25);
$pdf->SetAutoPageBreak(TRUE, 30);

$pdf->SetFont('dejavusans', '', 10);
$pdf->AddPage();

$html = '<h2 style="text-align:center; color:#0d6efd;">Başkanlık İstişare Sonuçları</h2>';
$html .= '<p style="text-align:center;">Toplam Geçerli Oy Formu: <strong>'.count($votes).'</strong></p>';
$html .= '<hr><br>';

$html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse;">';
$html .= '<tr style="background-color:#f8f9fa; font-weight:bold; text-align:center;">
    <td width="30">#</td>
    <td width="150" style="text-align:left;">Aday İsmi</td>
    <td width="150">Sıralama (1. - 5.)</td>
    <td width="60">Toplam</td>
    <td width="60">Puan</td>
</tr>';

$rank = 1;
foreach ($stats as $name => $s) {
    if ($rank % 2 == 0) {
        $bg = 'background-color:#fefefe;';
    } else {
        $bg = 'background-color:#f4f6f9;';
    }
    
    $ranksHtml = '';
    for($i=1; $i<=5; $i++) {
        if($s['ranks'][$i] > 0) {
            $ranksHtml .= $i.'. Tercih: '.$s['ranks'][$i].' defa<br>';
        }
    }

    $html .= '<tr style="'.$bg.' text-align:center;">
        <td width="30">'.$rank++.'</td>
        <td width="150" style="text-align:left; font-weight:bold;">'.htmlspecialchars($name).'</td>
        <td width="150" style="text-align:left; font-size:9px;">'.$ranksHtml.'</td>
        <td width="60">'.$s['total'].'</td>
        <td width="60" style="font-weight:bold;">'.number_format($s['score'], 2).'</td>
    </tr>';
}

$html .= '</table>';
$pdf->writeHTML($html, true, false, true, false, '');

// Sadece Adminler Son Oyları Görebilsin
$auth = new Auth();
if ($auth->isSuperAdmin()) {
    $lastVotes = $db->fetchAll("
        SELECT t1.* 
        FROM istisare_oylama t1
        INNER JOIN (
            SELECT voter_id, MAX(id) AS latest_id
            FROM istisare_oylama
            GROUP BY voter_id
        ) t2 ON t1.id = t2.latest_id
        ORDER BY t1.tarih DESC
    ");

    $pdf->AddPage();
    $html2 = '<h3 style="color:#0d6efd;">Detaylı Oy Dökümü</h3>';
    $html2 .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:8px;">';
    $html2 .= '<tr style="background-color:#e9ecef; font-weight:bold; text-align:left;">
        <td width="60">Oy Veren</td>
        <td width="40">Şube</td>
        <td width="60">1. Tercih</td>
        <td width="60">2. Tercih</td>
        <td width="60">3. Tercih</td>
        <td width="60">4. Tercih</td>
        <td width="60">5. Tercih</td>
        <td width="50">Notlar</td>
    </tr>';

    foreach ($lastVotes as $lv) {
        $html2 .= '<tr nobr="true">
            <td>'.htmlspecialchars($lv['voter_id']).'</td>
            <td>'.htmlspecialchars($lv['sube_ismi'] ?? '-').'</td>
            <td>'.htmlspecialchars($lv['secilen_1']).'</td>
            <td>'.htmlspecialchars($lv['secilen_2']).'</td>
            <td>'.htmlspecialchars($lv['secilen_3']).'</td>
            <td>'.htmlspecialchars($lv['secilen_4']).'</td>
            <td>'.htmlspecialchars($lv['secilen_5']).'</td>
            <td>'.htmlspecialchars($lv['notlar'] ?? '-').'</td>
        </tr>';
    }
    $html2 .= '</table>';
    
    $pdf->writeHTML($html2, true, false, true, false, '');
}

$pdf->Output('Istisare_Sonuclari_'.date('Ymd').'.pdf', 'I');

