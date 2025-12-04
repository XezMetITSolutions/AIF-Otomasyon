<?php
// Error logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Log incoming request for debugging
error_log('receive_pdf.php called - Method: ' . $_SERVER['REQUEST_METHOD']);

// JSON-Verzeichnis/Datei
$dataFile = 'submissions.json';

// Datei initialisieren
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([]));
}

$submissionsRaw = file_get_contents($dataFile);
$submissions = json_decode($submissionsRaw, true);
if (!is_array($submissions)) {
    $submissions = [];
}

// POST Felder (vom Frontend)
$name = trim($_POST['name'] ?? '');
$surname = trim($_POST['surname'] ?? '');
$iban = trim($_POST['iban'] ?? '');
$total = trim($_POST['total'] ?? '');
$itemsJson = $_POST['items_json'] ?? '';

// Validate required fields
if (empty($name) || empty($surname) || empty($iban)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Name, Surname und IBAN sind erforderlich.']);
    error_log('Validation failed: Missing required fields');
    exit;
}
$items = [];
$giderNo = 0;
if (!empty($itemsJson)) {
    $decoded = json_decode($itemsJson, true);
    if (is_array($decoded)) {
        // Yeni format: { gider_no: N, items: [...] }
        if (isset($decoded['items']) && is_array($decoded['items'])) {
            $giderNo = (int)($decoded['gider_no'] ?? 0);
            $itemsSrc = $decoded['items'];
        } else {
            // Eski format: doğrudan dizi
            $itemsSrc = $decoded;
        }
        // Birim değerlerini normalize et (analitik için)
        $items = array_map(function ($it) {
            $it = is_array($it) ? $it : [];
            $it['birim'] = normalize_key($it['birim'] ?? '');
            return $it;
        }, $itemsSrc);
    }
}

// TR karakterleri ascii'ye cevir ve kucuk harfe indir, [a-z] disini temizle
function normalize_key(string $value): string {
    $map = [
        'Ç' => 'C','Ğ' => 'G','İ' => 'I','I' => 'I','Ö' => 'O','Ş' => 'S','Ü' => 'U',
        'ç' => 'c','ğ' => 'g','ı' => 'i','i' => 'i','ö' => 'o','ş' => 's','ü' => 'u'
    ];
    $v = strtr($value, $map);
    $v = strtolower($v);
    $v = preg_replace('/[^a-z]/', '', $v);
    return $v ?? '';
}

// Upload-Verzeichnis sicherstellen (relative path)
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    if (!@mkdir($uploadDir, 0775, true)) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Upload-Verzeichnis konnte nicht erstellt werden.']);
        exit;
    }
}

// PDF empfangen
if (!isset($_FILES['pdf'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Keine PDF-Datei hochgeladen.']);
    error_log('No PDF file in request');
    exit;
}

$uploadError = $_FILES['pdf']['error'] ?? UPLOAD_ERR_NO_FILE;
if ($uploadError !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'Die Datei ist zu groß (server limit).',
        UPLOAD_ERR_FORM_SIZE => 'Die Datei ist zu groß (form limit).',
        UPLOAD_ERR_PARTIAL => 'Die Datei wurde nur teilweise hochgeladen.',
        UPLOAD_ERR_NO_FILE => 'Keine Datei wurde hochgeladen.',
        UPLOAD_ERR_NO_TMP_DIR => 'Temporäres Verzeichnis fehlt.',
        UPLOAD_ERR_CANT_WRITE => 'Fehler beim Schreiben der Datei.',
        UPLOAD_ERR_EXTENSION => 'Upload durch PHP-Erweiterung gestoppt.'
    ];
    $errorMsg = $errorMessages[$uploadError] ?? 'Unbekannter Upload-Fehler.';
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $errorMsg]);
    error_log('Upload error: ' . $uploadError . ' - ' . $errorMsg);
    exit;
}

if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
    $uniqueFileName = 'spesenformular_' . time() . '_' . uniqid() . '.pdf';
    $uploadFilePath = $uploadDir . $uniqueFileName;
    
    error_log('Attempting to move uploaded file to: ' . $uploadFilePath);

    if (move_uploaded_file($_FILES['pdf']['tmp_name'], $uploadFilePath)) {
        error_log('File uploaded successfully: ' . $uploadFilePath);
        // Store relative path for web access
        $relativeLink = 'uploads/' . $uniqueFileName;
        
        $submission = [
            'id' => uniqid('sub_', true),
            'isim' => $name,
            'soyisim' => $surname,
            'iban' => $iban,
            'total' => $total,
            'status' => 'Eingereicht',
            'pdf_link' => $relativeLink,
            'created_at' => date('c'),
            'items' => $items,
            'gider_no' => $giderNo
        ];

        $submissions[] = $submission;
        file_put_contents($dataFile, json_encode($submissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        echo json_encode(['status' => 'success', 'message' => 'PDF erfolgreich hochgeladen.']);
        exit;
    } else {
        http_response_code(500);
        $error = error_get_last();
        error_log('Failed to move uploaded file. Error: ' . print_r($error, true));
        echo json_encode(['status' => 'error', 'message' => 'Fehler beim Hochladen der Datei. Bitte überprüfen Sie die Verzeichnisberechtigungen.']);
        exit;
    }
}

http_response_code(400);
error_log('Reached end of script without processing file');
echo json_encode(['status' => 'error', 'message' => 'Keine Datei empfangen.']);
