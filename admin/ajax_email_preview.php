<?php
/**
 * Email Template Preview AJAX Handler
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Mail.php';

Middleware::requireSuperAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']);
    exit;
}

$content = $_POST['content'] ?? '';
$subject = $_POST['subject'] ?? '';

// Sample Data
$sampleData = [
    'ad_soyad' => 'Ahmet Yılmaz',
    'baslik' => 'Yönetim Kurulu Aylık İstişare Toplantısı',
    'tarih' => date('d.m.Y H:i', strtotime('+2 days 18:00')),
    'konum' => 'AİF Genel Merkez, 3. Kat Toplantı Salonu',
    'gundem_html' => '<ul><li>2025 Yılı bütçe planlaması</li><li>Yeni üye kayıtlarının değerlendirilmesi</li><li>Gelecek ayın etkinlik takvimi</li><li>Dilek ve temenniler</li></ul>',
    'accept_url' => '#',
    'reject_url' => '#',
    'app_name' => Config::get('app_name', 'AIF Otomasyon Sistemi'),
    'app_url' => Config::get('app_url', 'https://aifnet.islamfederasyonu.at'),
    'year' => date('Y'),
    'iptal_nedeni' => 'Yoğun hava muhalefeti ve ulaşım aksaklıkları nedeniyle toplantı ileri bir tarihe ertelenmiştir.'
];

$renderedContent = Mail::parseTemplate($content, $sampleData);
$renderedSubject = Mail::parseTemplate($subject, $sampleData);

echo json_encode([
    'success' => true,
    'subject' => $renderedSubject,
    'html' => $renderedContent
]);
