<?php
require_once 'auth.php';

// Login kontrolü kaldırıldı - direkt erişim
$currentUser = SessionManager::getCurrentUser();
$username = $currentUser['username'] ?? 'admin';

// Varsayılan veriler
$users = [
    ['id' => 1, 'username' => 'admin', 'full_name' => 'Sistem Yöneticisi', 'role' => 'superadmin', 'status' => 'active'],
    ['id' => 2, 'username' => 'AIF-Admin', 'full_name' => 'AIF Yöneticisi', 'role' => 'superadmin', 'status' => 'active']
];

$bykStats = [
    'total_users' => 2,
    'active_users' => 2,
    'by_role' => ['superadmin' => 2],
    'by_byk' => []
];

$userPermissions = [
    ['name' => 'users', 'display_name' => 'Kullanıcı Yönetimi', 'can_read' => true, 'can_write' => true, 'can_admin' => true]
];
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
                    <button class="btn btn-primary" onclick="addUser()">
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
                            <h3 class="text-info"><?php echo count(array_filter($users, function($user) { return isset($user['role']) && $user['role'] === 'manager'; })); ?></h3>
                            <p class="text-muted mb-0">Manager Kullanıcı</p>
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
                                <option value="manager">Manager</option>
                                <option value="member">Üye</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-primary w-100" onclick="filterUsers()">
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
                                            'manager' => 'bg-primary',
                                            'member' => 'bg-success'
                                        ];
                                        $roleNames = [
                                            'superadmin' => 'Superadmin',
                                            'manager' => 'Manager',
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
                                        <?php if (PermissionManager::canWrite($username, 'users')): ?>
                                        <button class="btn btn-sm btn-outline-primary me-1" title="Düzenle" onclick="editUser('<?php echo $username; ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (PermissionManager::canAdmin($username, 'users')): ?>
                                        <button class="btn btn-sm btn-outline-danger" title="Sil" onclick="deleteUser('<?php echo $username; ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (!PermissionManager::canWrite($username, 'users') && !PermissionManager::canAdmin($username, 'users')): ?>
                                        <span class="text-muted">Sadece Görüntüleme</span>
                                        <?php endif; ?>
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

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
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
        function addUser() {
            showAlert('Kullanıcı ekleme özelliği aktif!', 'success');
            console.log('Adding user...');
        }
        
        function editUser(username) {
            showAlert('Kullanıcı düzenleme özelliği aktif!', 'success');
            console.log('Editing user:', username);
        }
        
        function deleteUser(username) {
            if (confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?')) {
                showAlert('Kullanıcı silme özelliği aktif!', 'success');
                console.log('Deleting user:', username);
            }
        }
        
        function filterUsers() {
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

        
        // User management functions
        function addUser() {
            showAlert('Kullanıcı ekleme özelliği aktif!', 'success');
            console.log('Adding user...');
        }
        
        function editUser(username) {
            showAlert('Kullanıcı düzenleme özelliği aktif!', 'success');
            console.log('Editing user:', username);
        }
        
        function deleteUser(username) {
            if (confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?')) {
                showAlert('Kullanıcı silme özelliği aktif!', 'success');
                console.log('Deleting user:', username);
            }
        }
        
        function filterUsers() {
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
