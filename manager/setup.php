<?php
// Veritabanı otomatik kurulum sayfası
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veritabanı Kurulum - AIF Otomasyon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card { border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .step { margin-bottom: 20px; }
        .step-number { 
            background: #28a745; 
            color: white; 
            width: 30px; 
            height: 30px; 
            border-radius: 50%; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            margin-right: 10px; 
        }
        .log { 
            background: #f8f9fa; 
            border: 1px solid #dee2e6; 
            border-radius: 5px; 
            padding: 15px; 
            font-family: monospace; 
            font-size: 12px; 
            max-height: 300px; 
            overflow-y: auto; 
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-database me-2"></i>
                            Veritabanı Otomatik Kurulum
                        </h4>
                    </div>
                    <div class="card-body">
                        
                        <!-- Kurulum Adımları -->
                        <div class="step">
                            <h5><span class="step-number">1</span>Mevcut Tabloları Kontrol Et</h5>
                            <button class="btn btn-outline-info" onclick="checkExistingTables()">
                                <i class="fas fa-search me-2"></i>Mevcut Tabloları Kontrol Et
                            </button>
                            <div id="tableCheckResult" class="mt-2"></div>
                        </div>

                        <div class="step">
                            <h5><span class="step-number">2</span>Veritabanı Bağlantı Testi</h5>
                            <button class="btn btn-outline-primary" onclick="testConnection()" disabled id="testConnectionBtn">
                                <i class="fas fa-plug me-2"></i>Bağlantıyı Test Et
                            </button>
                            <div id="connectionResult" class="mt-2"></div>
                        </div>

                        <div class="step">
                            <h5><span class="step-number">3</span>Tablo Kurulumu</h5>
                            <button class="btn btn-outline-success" onclick="createTables()" disabled id="createTablesBtn">
                                <i class="fas fa-table me-2"></i>Tabloları Oluştur/Güncelle
                            </button>
                            <div id="tableResult" class="mt-2"></div>
                        </div>

                        <div class="step">
                            <h5><span class="step-number">4</span>Örnek Veri Ekleme</h5>
                            <button class="btn btn-outline-warning" onclick="insertSampleData()" disabled id="sampleDataBtn">
                                <i class="fas fa-plus me-2"></i>Örnek Veri Ekle
                            </button>
                            <div id="sampleDataResult" class="mt-2"></div>
                        </div>

                        <div class="step">
                            <h5><span class="step-number">5</span>Kurulum Tamamlama</h5>
                            <button class="btn btn-success" onclick="completeSetup()" disabled id="completeBtn">
                                <i class="fas fa-check me-2"></i>Kurulumu Tamamla
                            </button>
                            <div id="completeResult" class="mt-2"></div>
                        </div>

                        <!-- Log Alanı -->
                        <div class="mt-4">
                            <h6><i class="fas fa-terminal me-2"></i>Kurulum Logları</h6>
                            <div id="logArea" class="log">
                                <div class="text-muted">Kurulum başlatıldığında loglar burada görünecek...</div>
                            </div>
                        </div>

                        <!-- İlerleme Çubuğu -->
                        <div class="mt-4">
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: 0%" id="progressBar"></div>
                            </div>
                            <small class="text-muted mt-1">İlerleme: <span id="progressText">0%</span></small>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentStep = 0;
        const totalSteps = 5;
        let existingTables = [];

        function log(message, type = 'info') {
            const logArea = document.getElementById('logArea');
            const timestamp = new Date().toLocaleTimeString();
            const className = type === 'error' ? 'error' : type === 'success' ? 'success' : type === 'warning' ? 'warning' : '';
            
            const logEntry = document.createElement('div');
            logEntry.className = className;
            logEntry.innerHTML = `[${timestamp}] ${message}`;
            
            logArea.appendChild(logEntry);
            logArea.scrollTop = logArea.scrollHeight;
        }

        function updateProgress(step) {
            const percentage = (step / totalSteps) * 100;
            document.getElementById('progressBar').style.width = percentage + '%';
            document.getElementById('progressText').textContent = Math.round(percentage) + '%';
        }

        function enableNextStep(stepNumber) {
            if (stepNumber === 2) {
                document.getElementById('testConnectionBtn').disabled = false;
            } else if (stepNumber === 3) {
                document.getElementById('createTablesBtn').disabled = false;
            } else if (stepNumber === 4) {
                document.getElementById('sampleDataBtn').disabled = false;
            } else if (stepNumber === 5) {
                document.getElementById('completeBtn').disabled = false;
            }
        }

        async function checkExistingTables() {
            log('Mevcut tablolar kontrol ediliyor...', 'info');
            
            try {
                const response = await fetch('setup_db.php?action=check_tables');
                const result = await response.json();
                
                if (result.success) {
                    existingTables = result.tables;
                    log('✓ Tablo kontrolü tamamlandı', 'success');
                    
                    let tableInfo = '<div class="alert alert-info"><h6>Mevcut Tablolar:</h6><ul>';
                    
                    const requiredTables = ['users', 'expenses', 'expense_items'];
                    const missingTables = [];
                    
                    requiredTables.forEach(table => {
                        if (existingTables.includes(table)) {
                            tableInfo += `<li class="text-success">✓ ${table} - Mevcut</li>`;
                        } else {
                            tableInfo += `<li class="text-danger">✗ ${table} - Eksik</li>`;
                            missingTables.push(table);
                        }
                    });
                    
                    tableInfo += '</ul>';
                    
                    if (missingTables.length > 0) {
                        tableInfo += `<p class="text-warning"><strong>Eksik tablolar:</strong> ${missingTables.join(', ')}</p>`;
                        log(`Eksik tablolar tespit edildi: ${missingTables.join(', ')}`, 'warning');
                    } else {
                        tableInfo += '<p class="text-success"><strong>Tüm tablolar mevcut!</strong></p>';
                        log('Tüm gerekli tablolar mevcut', 'success');
                    }
                    
                    tableInfo += '</div>';
                    
                    document.getElementById('tableCheckResult').innerHTML = tableInfo;
                    currentStep = 1;
                    updateProgress(currentStep);
                    enableNextStep(2);
                    
                } else {
                    log('✗ Tablo kontrolü hatası: ' + result.message, 'error');
                    document.getElementById('tableCheckResult').innerHTML = 
                        '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>' + result.message + '</div>';
                }
            } catch (error) {
                log('✗ Tablo kontrolü hatası: ' + error.message, 'error');
                document.getElementById('tableCheckResult').innerHTML = 
                    '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Tablo kontrolü başarısız!</div>';
            }
        }

        async function testConnection() {
            log('Veritabanı bağlantısı test ediliyor...', 'info');
            
            try {
                const response = await fetch('setup_db.php?action=test');
                const result = await response.json();
                
                if (result.success) {
                    log('✓ Veritabanı bağlantısı başarılı!', 'success');
                    document.getElementById('connectionResult').innerHTML = 
                        '<div class="alert alert-success"><i class="fas fa-check me-2"></i>Bağlantı başarılı!</div>';
                    currentStep = 2;
                    updateProgress(currentStep);
                    enableNextStep(3);
                } else {
                    log('✗ Veritabanı bağlantı hatası: ' + result.message, 'error');
                    document.getElementById('connectionResult').innerHTML = 
                        '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>' + result.message + '</div>';
                }
            } catch (error) {
                log('✗ Bağlantı testi hatası: ' + error.message, 'error');
                document.getElementById('connectionResult').innerHTML = 
                    '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Bağlantı testi başarısız!</div>';
            }
        }

        async function createTables() {
            log('Tablolar oluşturuluyor...', 'info');
            
            try {
                const response = await fetch('setup_db.php?action=create_tables');
                const result = await response.json();
                
                if (result.success) {
                    log('✓ Tablolar başarıyla oluşturuldu!', 'success');
                    document.getElementById('tableResult').innerHTML = 
                        '<div class="alert alert-success"><i class="fas fa-check me-2"></i>Tablolar oluşturuldu!</div>';
                    currentStep = 3;
                    updateProgress(currentStep);
                    enableNextStep(4);
                } else {
                    log('✗ Tablo oluşturma hatası: ' + result.message, 'error');
                    document.getElementById('tableResult').innerHTML = 
                        '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>' + result.message + '</div>';
                }
            } catch (error) {
                log('✗ Tablo oluşturma hatası: ' + error.message, 'error');
                document.getElementById('tableResult').innerHTML = 
                    '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Tablo oluşturma başarısız!</div>';
            }
        }

        async function insertSampleData() {
            log('Örnek veri ekleniyor...', 'info');
            
            try {
                const response = await fetch('setup_db.php?action=insert_sample');
                const result = await response.json();
                
                if (result.success) {
                    log('✓ Örnek veri başarıyla eklendi!', 'success');
                    document.getElementById('sampleDataResult').innerHTML = 
                        '<div class="alert alert-success"><i class="fas fa-check me-2"></i>Örnek veri eklendi!</div>';
                    currentStep = 4;
                    updateProgress(currentStep);
                    enableNextStep(5);
                } else {
                    log('✗ Örnek veri ekleme hatası: ' + result.message, 'error');
                    document.getElementById('sampleDataResult').innerHTML = 
                        '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>' + result.message + '</div>';
                }
            } catch (error) {
                log('✗ Örnek veri ekleme hatası: ' + error.message, 'error');
                document.getElementById('sampleDataResult').innerHTML = 
                    '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Örnek veri ekleme başarısız!</div>';
            }
        }

        async function completeSetup() {
            log('Kurulum tamamlanıyor...', 'info');
            
            try {
                const response = await fetch('setup_db.php?action=complete');
                const result = await response.json();
                
                if (result.success) {
                    log('✓ Kurulum başarıyla tamamlandı!', 'success');
                    document.getElementById('completeResult').innerHTML = 
                        '<div class="alert alert-success"><i class="fas fa-check me-2"></i>Kurulum tamamlandı! <a href="expenses.php" class="btn btn-primary ms-2">Admin Paneline Git</a></div>';
                    currentStep = 5;
                    updateProgress(currentStep);
                } else {
                    log('✗ Kurulum tamamlama hatası: ' + result.message, 'error');
                    document.getElementById('completeResult').innerHTML = 
                        '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>' + result.message + '</div>';
                }
            } catch (error) {
                log('✗ Kurulum tamamlama hatası: ' + error.message, 'error');
                document.getElementById('completeResult').innerHTML = 
                    '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Kurulum tamamlama başarısız!</div>';
            }
        }

        // Sayfa yüklendiğinde bağlantı testini otomatik başlat
        window.onload = function() {
            log('Veritabanı kurulum sayfası yüklendi', 'info');
            log('Gerçek veritabanı bilgileri: Host=localhost, DB=d0451622, User=d0451622', 'info');
        };
    </script>
</body>
</html>
