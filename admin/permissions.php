<?php
require_once 'auth.php';
require_once 'includes/user_manager_db.php';

// Geçici olarak session kontrolü devre dışı
// SessionManager::requireRole(['superadmin', 'manager']);
$currentUser = SessionManager::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIF Otomasyon - Yetki Yönetimi</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <style>
        <?php include 'includes/styles.php'; ?>
        
        .permission-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .permission-card {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            background: #fff;
        }
        
        .permission-card h6 {
            margin-bottom: 0.75rem;
            color: #495057;
            font-weight: 600;
        }
        
        .permission-options {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .permission-option {
            flex: 1;
            min-width: 80px;
        }
        
        .permission-option input[type="radio"] {
            display: none;
        }
        
        .permission-option label {
            display: block;
            padding: 0.5rem;
            text-align: center;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
        }
        
        .permission-option input[type="radio"]:checked + label {
            background-color: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }
        
        .permission-option label:hover {
            background-color: #f8f9fa;
        }
        
        .permission-option input[type="radio"]:checked + label:hover {
            background-color: #0b5ed7;
        }
        
        .user-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }
        
        .alert-custom {
            border-radius: 0.5rem;
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
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
                    <h1><i class="fas fa-key"></i> Yetki Yönetimi</h1>
                </div>
                <div class="header-actions">
                    <button class="btn btn-success" onclick="saveUserPermissions()" id="saveBtn" disabled>
                        <i class="fas fa-save"></i> Yetkileri Kaydet
                    </button>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Alert Container -->
            <div id="alertContainer"></div>

            <!-- User Selection -->
            <div class="page-card">
                <div class="card-header">
                    <h5><i class="fas fa-users"></i> Kullanıcı Seçimi</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="permissionUserSelect" class="form-label">Kullanıcı Seçin:</label>
                            <select class="form-select" id="permissionUserSelect" onchange="loadUserPermissions()">
                                <option value="">Kullanıcı seçin...</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button class="btn btn-outline-primary" onclick="loadUserPermissions()" id="loadBtn" disabled>
                                    <i class="fas fa-sync"></i> Yetkileri Yükle
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Info -->
            <div id="userInfoContainer" class="user-info" style="display: none;">
                <h5 id="selectedUserName"></h5>
            </div>

            <!-- Permissions Container -->
            <div id="userPermissionsContainer" style="display: none;">
                <div class="page-card">
                    <div class="card-header">
                        <h5><i class="fas fa-key"></i> Modül Yetkileri</h5>
                    </div>
                    <div class="card-body">
                        <div id="permissionGrid">
                            <!-- Permissions will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let currentUserPermissions = {};
        let originalUserPermissions = {};

        // Sayfa yüklendiğinde kullanıcıları yükle
        $(document).ready(function() {
            console.log('Document ready');
            loadUsers();
        });

        function loadUsers() {
            console.log('loadUsers çağrıldı');
            fetch('get_user.php?action=list')
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Users data:', data);
                if (data.success) {
                    const select = $('#permissionUserSelect');
                    select.empty().append('<option value="">Kullanıcı seçin...</option>');
                    
                    data.users.forEach(user => {
                        select.append(`<option value="${user.username}">${user.first_name} ${user.last_name} (@${user.username})</option>`);
                    });
                } else {
                    showAlert('Kullanıcılar yüklenirken hata oluştu: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Kullanıcılar yüklenirken hata oluştu!', 'danger');
            });
        }

        function loadUserPermissions() {
            const username = $('#permissionUserSelect').val();
            if (!username) {
                $('#userPermissionsContainer').hide();
                $('#userInfoContainer').hide();
                $('#saveBtn').prop('disabled', true);
                return;
            }

            $('#permissionGrid').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Yetkiler yükleniyor...</div>');

            // Kullanıcı bilgilerini al
            fetch(`get_user.php?username=${username}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const user = data.user;
                    $('#selectedUserName').text(`${user.first_name} ${user.last_name} (@${user.username}) - Yetki Yönetimi`);
                    $('#userInfoContainer').show();
                    
                    // Yetkileri al
                    return fetch('manage_user_permissions.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'get',
                            username: username
                        })
                    });
                } else {
                    showAlert('Kullanıcı bulunamadı!', 'danger');
                    throw new Error('User not found');
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentUserPermissions = data.permissions || {};
                    originalUserPermissions = {...currentUserPermissions};
                    generatePermissionGrid();
                    $('#userPermissionsContainer').show();
                    $('#saveBtn').prop('disabled', false);
                } else {
                    $('#permissionGrid').html('<div class="alert alert-danger">Yetkiler yüklenirken hata oluştu: ' + data.message + '</div>');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                $('#permissionGrid').html('<div class="alert alert-danger">Yetkiler yüklenirken hata oluştu!</div>');
            });
        }

        function generatePermissionGrid() {
            const modules = [
                { name: 'dashboard', display: 'Dashboard' },
                { name: 'users', display: 'Kullanıcı Yönetimi' },
                { name: 'permissions', display: 'Yetki Yönetimi' },
                { name: 'calendar', display: 'Takvim' },
                { name: 'meeting_reports', display: 'Toplantı Raporları' },
                { name: 'reservations', display: 'Rezervasyonlar (Üye)' },
                { name: 'reservations_admin', display: 'Rezervasyonlar (Yönetici)' },
                { name: 'expenses', display: 'İade Talepleri (Üye)' },
                { name: 'expenses_admin', display: 'İade Talepleri (Yönetici)' },
                { name: 'inventory', display: 'Demirbaş Listesi (Üye)' },
                { name: 'inventory_admin', display: 'Demirbaş Listesi (Yönetici)' },
                { name: 'announcements', display: 'Duyurular' },
                { name: 'events', display: 'Etkinlikler' },
                { name: 'projects', display: 'Proje Takibi' },
                { name: 'reports', display: 'Raporlar' },
                { name: 'settings', display: 'Ayarlar' }
            ];

            let gridHtml = '<div class="permission-grid">';
            
            modules.forEach(module => {
                const currentLevel = currentUserPermissions[module.name] || 'none';
                
                gridHtml += `
                    <div class="permission-card">
                        <h6>${module.display}</h6>
                        <div class="permission-options">
                            <div class="permission-option">
                                <input type="radio" name="permission_${module.name}" id="none_${module.name}" value="none" ${currentLevel === 'none' ? 'checked' : ''}>
                                <label for="none_${module.name}">Erişim Yok</label>
                            </div>
                            <div class="permission-option">
                                <input type="radio" name="permission_${module.name}" id="read_${module.name}" value="read" ${currentLevel === 'read' ? 'checked' : ''}>
                                <label for="read_${module.name}">Oku</label>
                            </div>
                            <div class="permission-option">
                                <input type="radio" name="permission_${module.name}" id="write_${module.name}" value="write" ${currentLevel === 'write' ? 'checked' : ''}>
                                <label for="write_${module.name}">Yaz</label>
                            </div>
                            <div class="permission-option">
                                <input type="radio" name="permission_${module.name}" id="manager_${module.name}" value="manager" ${currentLevel === 'manager' ? 'checked' : ''}>
                                <label for="manager_${module.name}">Yönetici</label>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            gridHtml += '</div>';
            $('#permissionGrid').html(gridHtml);
        }

        function saveUserPermissions() {
            const username = $('#permissionUserSelect').val();
            if (!username) {
                showAlert('Lütfen önce bir kullanıcı seçin!', 'warning');
                return;
            }

            // Mevcut yetkileri topla
            const permissions = {};
            $('input[name^="permission_"]:checked').each(function() {
                const name = $(this).attr('name').replace('permission_', '');
                const value = $(this).val();
                permissions[name] = value;
            });

            // Kaydetme işlemi
            $('#saveBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Kaydediliyor...');

            fetch('manage_user_permissions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'update',
                    username: username,
                    permissions: permissions
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    currentUserPermissions = permissions;
                    originalUserPermissions = {...permissions};
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Yetkiler kaydedilirken hata oluştu!', 'danger');
            })
            .finally(() => {
                $('#saveBtn').prop('disabled', false).html('<i class="fas fa-save"></i> Yetkileri Kaydet');
            });
        }

        function showAlert(message, type) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show alert-custom" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            $('#alertContainer').html(alertHtml);
            
            // 5 saniye sonra otomatik kapat
            setTimeout(() => {
                $('.alert').alert('close');
            }, 5000);
        }

        // Kullanıcı seçimi değiştiğinde butonları aktif et
        $('#permissionUserSelect').on('change', function() {
            const hasSelection = $(this).val() !== '';
            $('#loadBtn').prop('disabled', !hasSelection);
            $('#saveBtn').prop('disabled', true);
        });
    </script>
</body>
</html>