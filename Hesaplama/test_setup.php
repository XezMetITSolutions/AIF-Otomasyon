<?php
/**
 * Setup Test Script
 * Bu dosyayı tarayıcıda açarak kurulumun doğru olup olmadığını kontrol edebilirsiniz.
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AİF Gider Formu - Kurulum Testi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #009872;
            border-bottom: 3px solid #009872;
            padding-bottom: 10px;
        }
        .test-item {
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #ddd;
            background: #f9f9f9;
        }
        .success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .warning {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        .error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        .test-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .test-message {
            font-size: 14px;
            color: #666;
        }
        code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🔧 AİF Gider Formu - Kurulum Testi</h1>
        
        <?php
        $tests = [];
        
        // Test 1: PHP Version
        $phpVersion = phpversion();
        if (version_compare($phpVersion, '7.4.0', '>=')) {
            $tests[] = [
                'status' => 'success',
                'title' => 'PHP Versiyonu',
                'message' => "PHP $phpVersion - OK ✓"
            ];
        } else {
            $tests[] = [
                'status' => 'error',
                'title' => 'PHP Versiyonu',
                'message' => "PHP $phpVersion - Minimum PHP 7.4 gerekli!"
            ];
        }
        
        // Test 2: File Uploads
        $fileUploads = ini_get('file_uploads');
        if ($fileUploads) {
            $tests[] = [
                'status' => 'success',
                'title' => 'Dosya Yükleme',
                'message' => 'Dosya yükleme aktif ✓'
            ];
        } else {
            $tests[] = [
                'status' => 'error',
                'title' => 'Dosya Yükleme',
                'message' => 'Dosya yükleme devre dışı! php.ini\'de file_uploads = On yapın.'
            ];
        }
        
        // Test 3: Upload Max Filesize
        $maxFilesize = ini_get('upload_max_filesize');
        $maxFilesizeBytes = return_bytes($maxFilesize);
        if ($maxFilesizeBytes >= 10 * 1024 * 1024) { // 10MB
            $tests[] = [
                'status' => 'success',
                'title' => 'Maksimum Dosya Boyutu',
                'message' => "$maxFilesize - OK ✓"
            ];
        } else {
            $tests[] = [
                'status' => 'warning',
                'title' => 'Maksimum Dosya Boyutu',
                'message' => "$maxFilesize - Büyük PDF'ler için yetersiz olabilir. Önerilen: 20M"
            ];
        }
        
        // Test 4: Post Max Size
        $postMaxSize = ini_get('post_max_size');
        $postMaxSizeBytes = return_bytes($postMaxSize);
        if ($postMaxSizeBytes >= 15 * 1024 * 1024) { // 15MB
            $tests[] = [
                'status' => 'success',
                'title' => 'Maksimum POST Boyutu',
                'message' => "$postMaxSize - OK ✓"
            ];
        } else {
            $tests[] = [
                'status' => 'warning',
                'title' => 'Maksimum POST Boyutu',
                'message' => "$postMaxSize - Büyük formlar için yetersiz olabilir. Önerilen: 25M"
            ];
        }
        
        // Test 5: Uploads Directory
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            if (@mkdir($uploadDir, 0755, true)) {
                $tests[] = [
                    'status' => 'success',
                    'title' => 'Uploads Dizini',
                    'message' => 'Dizin oluşturuldu ✓'
                ];
            } else {
                $tests[] = [
                    'status' => 'error',
                    'title' => 'Uploads Dizini',
                    'message' => 'Dizin oluşturulamadı! Yazma izni gerekli.'
                ];
            }
        } else {
            if (is_writable($uploadDir)) {
                $tests[] = [
                    'status' => 'success',
                    'title' => 'Uploads Dizini',
                    'message' => 'Mevcut ve yazılabilir ✓'
                ];
            } else {
                $tests[] = [
                    'status' => 'error',
                    'title' => 'Uploads Dizini',
                    'message' => 'Dizin mevcut ama yazılamıyor! chmod 755 uploads/ yapın.'
                ];
            }
        }
        
        // Test 6: Submissions JSON
        $dataFile = __DIR__ . '/submissions.json';
        if (!file_exists($dataFile)) {
            if (@file_put_contents($dataFile, json_encode([]))) {
                $tests[] = [
                    'status' => 'success',
                    'title' => 'Submissions JSON',
                    'message' => 'Dosya oluşturuldu ✓'
                ];
            } else {
                $tests[] = [
                    'status' => 'error',
                    'title' => 'Submissions JSON',
                    'message' => 'Dosya oluşturulamadı! Yazma izni gerekli.'
                ];
            }
        } else {
            if (is_writable($dataFile)) {
                $tests[] = [
                    'status' => 'success',
                    'title' => 'Submissions JSON',
                    'message' => 'Mevcut ve yazılabilir ✓'
                ];
            } else {
                $tests[] = [
                    'status' => 'error',
                    'title' => 'Submissions JSON',
                    'message' => 'Dosya mevcut ama yazılamıyor! chmod 644 submissions.json yapın.'
                ];
            }
        }
        
        // Test 7: Required Files
        $requiredFiles = ['index.html', 'receive_pdf.php', 'admin_dashboard.php', 'login.php'];
        $missingFiles = [];
        foreach ($requiredFiles as $file) {
            if (!file_exists(__DIR__ . '/' . $file)) {
                $missingFiles[] = $file;
            }
        }
        
        if (empty($missingFiles)) {
            $tests[] = [
                'status' => 'success',
                'title' => 'Gerekli Dosyalar',
                'message' => 'Tüm dosyalar mevcut ✓'
            ];
        } else {
            $tests[] = [
                'status' => 'error',
                'title' => 'Gerekli Dosyalar',
                'message' => 'Eksik dosyalar: ' . implode(', ', $missingFiles)
            ];
        }
        
        // Test 8: JSON Extension
        if (function_exists('json_encode') && function_exists('json_decode')) {
            $tests[] = [
                'status' => 'success',
                'title' => 'JSON Extension',
                'message' => 'JSON desteği aktif ✓'
            ];
        } else {
            $tests[] = [
                'status' => 'error',
                'title' => 'JSON Extension',
                'message' => 'JSON extension yüklü değil!'
            ];
        }
        
        // Display results
        foreach ($tests as $test) {
            echo '<div class="test-item ' . $test['status'] . '">';
            echo '<div class="test-title">' . htmlspecialchars($test['title']) . '</div>';
            echo '<div class="test-message">' . htmlspecialchars($test['message']) . '</div>';
            echo '</div>';
        }
        
        // Summary
        $successCount = count(array_filter($tests, function($t) { return $t['status'] === 'success'; }));
        $totalCount = count($tests);
        
        echo '<div style="margin-top: 30px; padding: 20px; background: #e9ecef; border-radius: 5px;">';
        echo '<strong>Sonuç:</strong> ' . $successCount . '/' . $totalCount . ' test başarılı';
        
        if ($successCount === $totalCount) {
            echo '<br><span style="color: #28a745;">✓ Sistem kullanıma hazır!</span>';
        } else {
            echo '<br><span style="color: #dc3545;">⚠ Lütfen yukarıdaki hataları düzeltin.</span>';
        }
        echo '</div>';
        
        // Helper function
        function return_bytes($val) {
            $val = trim($val);
            $last = strtolower($val[strlen($val)-1]);
            $val = (int)$val;
            switch($last) {
                case 'g':
                    $val *= 1024;
                case 'm':
                    $val *= 1024;
                case 'k':
                    $val *= 1024;
            }
            return $val;
        }
        ?>
        
        <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-radius: 5px; border-left: 4px solid #ffc107;">
            <strong>⚠ Önemli:</strong> Bu test dosyasını canlı ortamda kullandıktan sonra silin!<br>
            <code>rm test_setup.php</code>
        </div>
        
        <div style="margin-top: 20px; text-align: center;">
            <a href="index.html" style="display: inline-block; padding: 12px 24px; background: #009872; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                Forma Git →
            </a>
        </div>
    </div>
</body>
</html>
