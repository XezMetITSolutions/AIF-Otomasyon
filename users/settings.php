<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIF Otomasyon - Sistem Ayarları</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <style>
        <?php include 'includes/styles.php'; ?>
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Sistem Ayarları</h1>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="saveSettings()">
                        <i class="fas fa-save"></i> Ayarları Kaydet
                    </button>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- General Settings -->
            <div class="page-card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-cog"></i> Genel Ayarlar</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Site Adı</label>
                            <input type="text" class="form-control" value="AIF Otomasyon">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Site URL</label>
                            <input type="url" class="form-control" value="https://otomasyon.metechnik.at">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">E-posta Adresi</label>
                            <input type="email" class="form-control" value="admin@aifotomasyon.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telefon</label>
                            <input type="tel" class="form-control" value="+90 212 555 0123">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Site Açıklaması</label>
                            <textarea class="form-control" rows="3">AIF Otomasyon sistemi ile işletmenizi dijitalleştirin.</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Settings -->
            <div class="page-card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-users"></i> Kullanıcı Ayarları</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Yeni Kayıt İzinleri</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="allowRegistration" checked>
                                <label class="form-check-label" for="allowRegistration">
                                    Yeni kullanıcı kayıtlarına izin ver
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">E-posta Doğrulama</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="emailVerification" checked>
                                <label class="form-check-label" for="emailVerification">
                                    E-posta doğrulama zorunlu
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Maksimum Giriş Denemesi</label>
                            <input type="number" class="form-control" value="5" min="1" max="10">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Oturum Süresi (dakika)</label>
                            <input type="number" class="form-control" value="120" min="30" max="1440">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Settings -->
            <div class="page-card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-envelope"></i> E-posta Ayarları</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SMTP Sunucusu</label>
                            <input type="text" class="form-control" value="smtp.gmail.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SMTP Port</label>
                            <input type="number" class="form-control" value="587">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">E-posta Adresi</label>
                            <input type="email" class="form-control" value="noreply@aifotomasyon.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Şifre</label>
                            <input type="password" class="form-control" value="••••••••">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Şifreleme</label>
                            <select class="form-select">
                                <option>TLS</option>
                                <option>SSL</option>
                                <option>None</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <button class="btn btn-outline-primary">
                                <i class="fas fa-paper-plane"></i> Test E-postası Gönder
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="page-card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-shield-alt"></i> Güvenlik Ayarları</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">İki Faktörlü Doğrulama</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="twoFactorAuth">
                                <label class="form-check-label" for="twoFactorAuth">
                                    İki faktörlü doğrulama aktif
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">IP Kısıtlaması</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="ipRestriction">
                                <label class="form-check-label" for="ipRestriction">
                                    IP kısıtlaması aktif
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Şifre Minimum Uzunluk</label>
                            <input type="number" class="form-control" value="8" min="6" max="20">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Şifre Karmaşıklığı</label>
                            <select class="form-select">
                                <option>Düşük</option>
                                <option selected>Orta</option>
                                <option>Yüksek</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">İzin Verilen IP Adresleri</label>
                            <textarea class="form-control" rows="3" placeholder="192.168.1.1&#10;10.0.0.1&#10;172.16.0.1"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backup Settings -->
            <div class="page-card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-database"></i> Yedekleme Ayarları</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Otomatik Yedekleme</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="autoBackup" checked>
                                <label class="form-check-label" for="autoBackup">
                                    Otomatik yedekleme aktif
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Yedekleme Sıklığı</label>
                            <select class="form-select">
                                <option>Günlük</option>
                                <option selected>Haftalık</option>
                                <option>Aylık</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Yedekleme Saati</label>
                            <input type="time" class="form-control" value="02:00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Saklama Süresi (gün)</label>
                            <input type="number" class="form-control" value="30" min="7" max="365">
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary">
                                    <i class="fas fa-download"></i> Manuel Yedekleme
                                </button>
                                <button class="btn btn-outline-success">
                                    <i class="fas fa-upload"></i> Yedekten Geri Yükle
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="page-card">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle"></i> Sistem Bilgileri</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">PHP Sürümü</label>
                            <input type="text" class="form-control" value="8.2.0" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Veritabanı Sürümü</label>
                            <input type="text" class="form-control" value="MySQL 8.0" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sunucu</label>
                            <input type="text" class="form-control" value="Apache 2.4" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Disk Kullanımı</label>
                            <input type="text" class="form-control" value="2.5 GB / 10 GB" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Son Güncelleme</label>
                            <input type="text" class="form-control" value="15.01.2024" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sistem Durumu</label>
                            <input type="text" class="form-control text-success" value="Çevrimiçi" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function saveSettings() {
            showAlert('Ayarlar başarıyla kaydedildi!', 'success');
            console.log('Saving settings...');
        }
        
        $(document).ready(function() {
            // Mobile sidebar toggle
            $('.navbar-toggler').click(function() {
                $('.sidebar').toggleClass('show');
            });
            
            // Form validation
            $('form').on('submit', function(e) {
                e.preventDefault();
                
                // Show success message
                showNotification('Ayarlar başarıyla kaydedildi!', 'success');
            });
            
            // Show notification function
            function showNotification(message, type) {
                const alertClass = type === 'success' ? 'alert-success' : 
                                  type === 'warning' ? 'alert-warning' : 
                                  type === 'danger' ? 'alert-danger' : 'alert-info';
                
                const alert = $(`
                    <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                         style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 
                                          type === 'warning' ? 'exclamation-triangle' : 
                                          type === 'danger' ? 'times-circle' : 'info-circle'}"></i>
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `);
                
                $('body').append(alert);
                
                // Auto remove after 5 seconds
                setTimeout(function() {
                    alert.alert('close');
                }, 5000);
            }
        });
    </script>
</body>
</html>
