<?php
require_once 'includes/auth.php';
require_once 'includes/user_manager_db.php';
require_once 'includes/byk_manager_db.php';

// Login kontrolü
SessionManager::requireRole(['superadmin', 'manager']);
$currentUser = SessionManager::getCurrentUser();

// BYK kategorilerini al
$bykCategories = BYKManager::getBYKCategories();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BYK Debug Sayfası - AIF Otomasyon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .debug-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .debug-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
        }
        .log-area {
            background: #1e1e1e;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            padding: 15px;
            border-radius: 5px;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        .test-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .btn-debug {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .btn-debug:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            color: white;
        }
        .status-success { color: #28a745; }
        .status-error { color: #dc3545; }
        .status-warning { color: #ffc107; }
        .status-info { color: #17a2b8; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <!-- Header -->
                <div class="debug-card">
                    <div class="debug-header">
                        <h2><i class="fas fa-bug"></i> BYK Debug Sayfası</h2>
                        <p class="mb-0">BYK kaydetme sorununu test etmek ve logları görüntülemek için</p>
                    </div>
                </div>

                <!-- Test Form -->
                <div class="test-form">
                    <h4><i class="fas fa-flask"></i> BYK Test Formu</h4>
                    <form id="bykTestForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Ad</label>
                                    <input type="text" class="form-control" name="first_name" value="Debug" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Soyad</label>
                                    <input type="text" class="form-control" name="last_name" value="Test" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">E-posta</label>
                                    <input type="email" class="form-control" name="email" value="debug.test@aif.com" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">BYK</label>
                                    <select class="form-select" name="byk" required>
                                        <option value="">BYK Seçin</option>
                                        <?php foreach ($bykCategories as $byk): ?>
                                            <option value="<?php echo $byk['code']; ?>"><?php echo $byk['code']; ?> - <?php echo $byk['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Rol</label>
                                    <select class="form-select" name="role" required>
                                        <option value="manager">Manager</option>
                                        <option value="member">Üye</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Durum</label>
                                    <select class="form-select" name="status" required>
                                        <option value="active">Aktif</option>
                                        <option value="inactive">Pasif</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-debug" onclick="testAddUser()">
                                <i class="fas fa-plus"></i> Test Kullanıcı Ekle
                            </button>
                            <button type="button" class="btn btn-warning" onclick="clearLogs()">
                                <i class="fas fa-trash"></i> Logları Temizle
                            </button>
                            <button type="button" class="btn btn-info" onclick="checkLogs()">
                                <i class="fas fa-eye"></i> Logları Kontrol Et
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Log Area -->
                <div class="debug-card">
                    <div class="card-header">
                        <h5><i class="fas fa-terminal"></i> Debug Logları</h5>
                    </div>
                    <div class="card-body">
                        <div id="logArea" class="log-area">
                            BYK Debug Logları burada görünecek...<br>
                            Test başlatmak için "Test Kullanıcı Ekle" butonuna tıklayın.
                        </div>
                    </div>
                </div>

                <!-- BYK Categories Info -->
                <div class="debug-card">
                    <div class="card-header">
                        <h5><i class="fas fa-list"></i> BYK Kategorileri</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Kod</th>
                                        <th>İsim</th>
                                        <th>Renk</th>
                                        <th>Açıklama</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bykCategories as $byk): ?>
                                        <tr>
                                            <td><?php echo $byk['id']; ?></td>
                                            <td><span class="badge bg-primary"><?php echo $byk['code']; ?></span></td>
                                            <td><?php echo $byk['name']; ?></td>
                                            <td><span class="badge" style="background-color: <?php echo $byk['color']; ?>"><?php echo $byk['color']; ?></span></td>
                                            <td><?php echo $byk['description']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addLog(message, type = 'info') {
            const logArea = document.getElementById('logArea');
            const timestamp = new Date().toLocaleTimeString();
            const typeClass = 'status-' + type;
            const logEntry = `[${timestamp}] <span class="${typeClass}">${message}</span><br>`;
            logArea.innerHTML += logEntry;
            logArea.scrollTop = logArea.scrollHeight;
        }

        function clearLogs() {
            document.getElementById('logArea').innerHTML = 'Loglar temizlendi...<br>';
        }

        function checkLogs() {
            addLog('Log kontrolü başlatılıyor...', 'info');
            
            fetch('debug_logs.php')
                .then(response => response.text())
                .then(data => {
                    addLog('Log dosyası içeriği:', 'info');
                    addLog(data, 'info');
                })
                .catch(error => {
                    addLog('Log kontrolü hatası: ' + error.message, 'error');
                });
        }

        function testAddUser() {
            const form = document.getElementById('bykTestForm');
            const formData = new FormData(form);
            const userData = Object.fromEntries(formData);
            
            addLog('=== BYK TEST BAŞLADI ===', 'info');
            addLog('Form verisi: ' + JSON.stringify(userData, null, 2), 'info');
            
            // BYK alanını düzelt (users.php'deki gibi)
            if (userData.byk_category) {
                userData.byk = userData.byk_category;
                delete userData.byk_category;
            }
            
            addLog('Düzeltilmiş veri: ' + JSON.stringify(userData, null, 2), 'info');
            
            fetch('add_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(userData)
            })
            .then(response => {
                addLog('Response status: ' + response.status, 'info');
                return response.json();
            })
            .then(data => {
                addLog('Response data: ' + JSON.stringify(data, null, 2), 'info');
                
                if (data.success) {
                    addLog('✅ Kullanıcı başarıyla eklendi!', 'success');
                    if (data.debug) {
                        addLog('🔍 Debug Info: BYK=' + data.debug.byk_input + ', ID=' + data.debug.byk_category_id, 'info');
                        addLog('🔍 BYK Found: ' + data.debug.byk_category_found, 'info');
                    }
                } else {
                    addLog('❌ Hata: ' + data.message, 'error');
                    if (data.debug) {
                        addLog('🔍 Debug Error: BYK=' + data.debug.byk_input + ', ID=' + data.debug.byk_category_id, 'error');
                    }
                }
            })
            .catch(error => {
                addLog('❌ Fetch hatası: ' + error.message, 'error');
            });
        }

        // Sayfa yüklendiğinde
        document.addEventListener('DOMContentLoaded', function() {
            addLog('BYK Debug Sayfası yüklendi', 'success');
            addLog('Test için formu doldurun ve "Test Kullanıcı Ekle" butonuna tıklayın', 'info');
        });
    </script>
</body>
</html>
