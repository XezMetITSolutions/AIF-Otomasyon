<?php
require_once 'auth.php';
require_once 'includes/byk_manager.php';

// Session kontrolü - sadece superadmin giriş yapabilir
SessionManager::requireRole('superadmin');

$currentUser = SessionManager::getCurrentUser();
$userManager = new UserManager();
$users = $userManager->getAllUsers();
$bykStats = BYKManager::getBYKStats();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIF Otomasyon - Kullanıcı Yönetimi</title>
    
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
                    <h1>Kullanıcı Yönetimi</h1>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-plus"></i> Yeni Kullanıcı
                    </button>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- BYK Statistics -->
            <div class="row mb-4">
                <?php foreach ($bykStats as $bykCode => $bykData): ?>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card byk-<?php echo strtolower($bykCode); ?>-border">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <span class="badge byk-<?php echo strtolower($bykCode); ?> fs-6"><?php echo $bykCode; ?></span>
                            </div>
                            <h3 class="text-byk-<?php echo strtolower($bykCode); ?>"><?php echo count(array_filter($users, function($user) use ($bykCode) { return isset($user['byk']) && $user['byk'] === $bykCode; })); ?></h3>
                            <p class="text-muted mb-0"><?php echo $bykData['name']; ?></p>
                            <small class="text-muted"><?php echo $bykData['sub_units_count']; ?> Alt Birim</small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- User Statistics -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-primary"><?php echo count($users); ?></h3>
                            <p class="text-muted mb-0">Toplam Kullanıcı</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-success"><?php echo count(array_filter($users, function($user) { return isset($user['status']) && $user['status'] === 'active'; })); ?></h3>
                            <p class="text-muted mb-0">Aktif Kullanıcı</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-info"><?php echo count(array_filter($users, function($user) { return isset($user['role']) && $user['role'] === 'admin'; })); ?></h3>
                            <p class="text-muted mb-0">Admin Kullanıcı</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-warning"><?php echo count(array_filter($users, function($user) { return isset($user['role']) && $user['role'] === 'member'; })); ?></h3>
                            <p class="text-muted mb-0">Üye Kullanıcı</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Management -->
            <div class="page-card">
                <div class="card-header">
                    <h5><i class="fas fa-users"></i> Kullanıcı Listesi</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" placeholder="Kullanıcı ara...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="bykFilter">
                                <option value="">Tüm BYK'lar</option>
                                <?php foreach (BYKManager::getBYKCategories() as $code => $name): ?>
                                <option value="<?php echo $code; ?>"><?php echo $code; ?> - <?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="roleFilter">
                                <option value="">Tüm Roller</option>
                                <option value="superadmin">Superadmin</option>
                                <option value="admin">Admin</option>
                                <option value="member">Üye</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-primary w-100">
                                <i class="fas fa-filter"></i> Filtrele
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Kullanıcı</th>
                                    <th>E-posta</th>
                                    <th>BYK</th>
                                    <th>Alt Birim</th>
                                    <th>Rol</th>
                                    <th>Durum</th>
                                    <th>Kayıt Tarihi</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $username => $user): ?>
                                <tr data-byk="<?php echo $user['byk'] ?? ''; ?>" data-role="<?php echo $user['role']; ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center text-white me-2">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                                <small class="text-muted">@<?php echo htmlspecialchars($username); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <?php if (isset($user['byk'])): ?>
                                        <span class="badge byk-<?php echo strtolower($user['byk']); ?>"><?php echo $user['byk']; ?></span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (isset($user['sub_unit'])): ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($user['sub_unit']); ?></span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $roleColors = [
                                            'superadmin' => 'bg-danger',
                                            'admin' => 'bg-primary',
                                            'member' => 'bg-success'
                                        ];
                                        $roleNames = [
                                            'superadmin' => 'Superadmin',
                                            'admin' => 'Admin',
                                            'member' => 'Üye'
                                        ];
                                        ?>
                                        <span class="badge <?php echo $roleColors[$user['role']] ?? 'bg-secondary'; ?>">
                                            <?php echo $roleNames[$user['role']] ?? $user['role']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Aktif</span>
                                    </td>
                                    <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1" title="Düzenle" onclick="editUser('<?php echo $username; ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" title="Sil" onclick="deleteUser('<?php echo $username; ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Kullanıcı Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ad Soyad</label>
                                <input type="text" class="form-control" name="full_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kullanıcı Adı</label>
                                <input type="text" class="form-control" name="username" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">E-posta</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Şifre</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">BYK</label>
                                <select class="form-select" name="byk">
                                    <option value="">BYK Seçin</option>
                                    <?php foreach (BYKManager::getBYKCategories() as $code => $name): ?>
                                    <option value="<?php echo $code; ?>"><?php echo $code; ?> - <?php echo $name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Alt Birim</label>
                                <select class="form-select" name="sub_unit">
                                    <option value="">Alt Birim Seçin</option>
                                    <?php foreach (BYKManager::getAllSubUnits() as $unit): ?>
                                    <option value="<?php echo $unit; ?>"><?php echo $unit; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rol</label>
                                <select class="form-select" name="role" required>
                                    <option value="member">Üye</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Durum</label>
                                <select class="form-select" name="status">
                                    <option value="active">Aktif</option>
                                    <option value="inactive">Pasif</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" onclick="saveUser()">Kaydet</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kullanıcı Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm">
                        <input type="hidden" name="username" id="editUsername">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ad Soyad</label>
                                <input type="text" class="form-control" name="full_name" id="editFullName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">E-posta</label>
                                <input type="email" class="form-control" name="email" id="editEmail" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">BYK</label>
                                <select class="form-select" name="byk" id="editBYK">
                                    <option value="">BYK Seçin</option>
                                    <?php foreach (BYKManager::getBYKCategories() as $code => $name): ?>
                                    <option value="<?php echo $code; ?>"><?php echo $code; ?> - <?php echo $name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Alt Birim</label>
                                <select class="form-select" name="sub_unit" id="editSubUnit">
                                    <option value="">Alt Birim Seçin</option>
                                    <?php foreach (BYKManager::getAllSubUnits() as $unit): ?>
                                    <option value="<?php echo $unit; ?>"><?php echo $unit; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rol</label>
                                <select class="form-select" name="role" id="editRole" required>
                                    <option value="member">Üye</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Durum</label>
                                <select class="form-select" name="status" id="editStatus">
                                    <option value="active">Aktif</option>
                                    <option value="inactive">Pasif</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Yeni Şifre (Opsiyonel)</label>
                            <input type="password" class="form-control" name="password" placeholder="Değiştirmek için yeni şifre girin">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" onclick="updateUser()">Güncelle</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // User data for editing
        const users = <?php echo json_encode($users); ?>;
        
        $(document).ready(function() {
            // Search functionality
            $('input[type="text"]').on('keyup', function() {
                const searchTerm = $(this).val().toLowerCase();
                $('tbody tr').each(function() {
                    const text = $(this).text().toLowerCase();
                    $(this).toggle(text.includes(searchTerm));
                });
            });
            
            // BYK Filter
            $('#bykFilter').on('change', function() {
                const selectedBYK = $(this).val();
                $('tbody tr').each(function() {
                    const rowBYK = $(this).data('byk');
                    if (selectedBYK === '' || rowBYK === selectedBYK) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
            
            // Role Filter
            $('#roleFilter').on('change', function() {
                const selectedRole = $(this).val();
                $('tbody tr').each(function() {
                    const rowRole = $(this).data('role');
                    if (selectedRole === '' || rowRole === selectedRole) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
            
            // Combined Filter
            $('.btn-outline-primary').on('click', function() {
                const searchTerm = $('input[type="text"]').val().toLowerCase();
                const selectedBYK = $('#bykFilter').val();
                const selectedRole = $('#roleFilter').val();
                
                $('tbody tr').each(function() {
                    const text = $(this).text().toLowerCase();
                    const rowBYK = $(this).data('byk');
                    const rowRole = $(this).data('role');
                    
                    const matchesSearch = searchTerm === '' || text.includes(searchTerm);
                    const matchesBYK = selectedBYK === '' || rowBYK === selectedBYK;
                    const matchesRole = selectedRole === '' || rowRole === selectedRole;
                    
                    $(this).toggle(matchesSearch && matchesBYK && matchesRole);
                });
            });
            
            // Mobile sidebar toggle
            $('.navbar-toggler').click(function() {
                $('.sidebar').toggleClass('show');
            });
        });
        
        // User management functions
        function saveUser() {
            const form = document.getElementById('addUserForm');
            const formData = new FormData(form);
            
            // Simulate API call
            showAlert('Kullanıcı başarıyla eklendi!', 'success');
            $('#addUserModal').modal('hide');
            form.reset();
            
            // In real implementation, you would send data to server
            console.log('Adding user:', Object.fromEntries(formData));
        }
        
        function editUser(username) {
            const user = users[username];
            if (!user) return;
            
            // Fill edit form with user data
            document.getElementById('editUsername').value = username;
            document.getElementById('editFullName').value = user.full_name || '';
            document.getElementById('editEmail').value = user.email || '';
            document.getElementById('editBYK').value = user.byk || '';
            document.getElementById('editSubUnit').value = user.sub_unit || '';
            document.getElementById('editRole').value = user.role || '';
            document.getElementById('editStatus').value = user.status || 'active';
            
            // Show edit modal
            $('#editUserModal').modal('show');
        }
        
        function updateUser() {
            const form = document.getElementById('editUserForm');
            const formData = new FormData(form);
            
            // Simulate API call
            showAlert('Kullanıcı başarıyla güncellendi!', 'success');
            $('#editUserModal').modal('hide');
            
            // In real implementation, you would send data to server
            console.log('Updating user:', Object.fromEntries(formData));
        }
        
        function deleteUser(username) {
            if (confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?')) {
                // Simulate API call
                showAlert('Kullanıcı başarıyla silindi!', 'success');
                
                // In real implementation, you would send delete request to server
                console.log('Deleting user:', username);
            }
        }
        
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 3000);
        }
        
        function logout() {
            if (confirm('Çıkış yapmak istediğinizden emin misiniz?')) {
                fetch('auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=logout'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '../index.php';
                    }
                });
            }
        }
    </script>
</body>
</html>
