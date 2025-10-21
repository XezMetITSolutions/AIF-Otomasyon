<?php
// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session kontrolü geçici olarak devre dışı
// require_once 'auth.php';
require_once 'includes/user_manager_db.php';

// Login kontrolü - GEÇİCİ OLARAK DEVRE DIŞI
// SessionManager::requireRole(['superadmin', 'manager']);
// $currentUser = SessionManager::getCurrentUser();

// Veritabanından kullanıcıları çek
try {
    $users = UserManager::getAllUsers();
    
    // Debug bilgisi
    error_log("Users count: " . count($users));
    if (empty($users)) {
        error_log("No users found in database");
    }
} catch (Exception $e) {
    // Hata durumunda varsayılan veriler
    error_log("Error getting users: " . $e->getMessage());
    $users = [];
    $userStats = [
        'total' => 0,
        'active' => 0,
        'by_role' => [],
        'by_byk' => []
    ];
}
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
        <header class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-users text-primary"></i> Kullanıcı Yönetimi</h1>
                    <p class="text-muted mb-0">Sistem kullanıcılarını yönetin ve organize edin</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="debug_users_page.php" class="btn btn-outline-info">
                        <i class="fas fa-bug"></i> Debug
                    </a>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-plus"></i> Yeni Kullanıcı
                    </button>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- User Management -->
            <div class="page-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><i class="fas fa-list text-primary"></i> Kullanıcı Listesi</h5>
                        <small class="text-muted">Sistemdeki tüm kullanıcıları görüntüleyin ve yönetin</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary btn-sm" onclick="exportUsers()">
                            <i class="fas fa-download"></i> Dışa Aktar
                        </button>
                        <button class="btn btn-outline-primary btn-sm" onclick="refreshUsers()">
                            <i class="fas fa-sync-alt"></i> Yenile
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    
                    <!-- Filters -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" placeholder="Kullanıcı ara...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="bykFilter">
                                <option value="">Tüm BYK'lar</option>
                                <!-- BYKManager geçici olarak devre dışı -->
                                <option value="AT">AT - Ana Teşkilat</option>
                                <option value="KT">KT - Kadınlar Teşkilatı</option>
                                <option value="KGT">KGT - Kadınlar Gençlik Teşkilatı</option>
                                <option value="GT">GT - Gençlik Teşkilatı</option>
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
                            <button class="btn btn-outline-secondary w-100" onclick="clearFilters()">
                                <i class="fas fa-times"></i> Temizle
                            </button>
                        </div>
                    </div>
                    
                    <!-- Users Table -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th><i class="fas fa-user text-primary"></i> Kullanıcı</th>
                                    <th><i class="fas fa-envelope text-primary"></i> E-posta</th>
                                    <th><i class="fas fa-tags text-primary"></i> BYK</th>
                                    <th><i class="fas fa-user-tag text-primary"></i> Rol</th>
                                    <th><i class="fas fa-circle text-primary"></i> Durum</th>
                                    <th class="text-center"><i class="fas fa-cogs text-primary"></i> İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($users)): ?>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                                <i class="fas fa-user text-primary"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold"><?php echo htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'İsimsiz'); ?></div>
                                                <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <?php if (!empty($user['byk_category'])): ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($user['byk_category']); ?></span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo ucfirst($user['role']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Aktif</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-outline-primary btn-sm" onclick="editUser('<?php echo $user['username']; ?>')" title="Düzenle">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger btn-sm" onclick="deleteUser('<?php echo $user['username']; ?>')" title="Sil">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-users fa-3x mb-3"></i>
                                            <h5>Henüz kullanıcı bulunmuyor</h5>
                                            <p>İlk kullanıcıyı eklemek için "Yeni Kullanıcı" butonuna tıklayın.</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
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
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Bilgi:</strong> Yeni kullanıcıların varsayılan şifresi <code>AIF571#</code> olarak ayarlanır. Kullanıcılar ilk girişlerinde şifrelerini değiştirmek zorundadır.
                    </div>
                    <form id="addUserForm">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Ad</label>
                                <input type="text" class="form-control" name="first_name" id="firstName" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Soyad</label>
                                <input type="text" class="form-control" name="last_name" id="lastName" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Kullanıcı Adı</label>
                                <input type="text" class="form-control" name="username" id="username" readonly>
                                <small class="text-muted">Otomatik oluşturulur</small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">E-posta</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Görev</label>
                                <select class="form-select" name="position" id="position">
                                    <option value="">Görev Seçin</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">BYK</label>
                                <select class="form-select" name="byk_category">
                                    <option value="">BYK Seçin</option>
                                    <option value="AT">AT - Ana Teşkilat</option>
                                    <option value="KT">KT - Kadınlar Teşkilatı</option>
                                    <option value="KGT">KGT - Kadınlar Gençlik Teşkilatı</option>
                                    <option value="GT">GT - Gençlik Teşkilatı</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rol</label>
                                <select class="form-select" name="role" required>
                                    <option value="">Rol Seçin</option>
                                    <option value="superadmin">Superadmin</option>
                                    <option value="manager">Manager</option>
                                    <option value="member">Üye</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
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
                    <button type="button" class="btn btn-primary" onclick="saveUser()">Kullanıcı Ekle</button>
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
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Ad</label>
                                <input type="text" class="form-control" name="first_name" id="editFirstName" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Soyad</label>
                                <input type="text" class="form-control" name="last_name" id="editLastName" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Kullanıcı Adı</label>
                                <input type="text" class="form-control" id="editUsernameDisplay" readonly>
                                <small class="text-muted">Değiştirilemez</small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">E-posta</label>
                                <input type="email" class="form-control" name="email" id="editEmail" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Görev</label>
                                <select class="form-select" name="position" id="editPosition">
                                    <option value="">Görev Seçin</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">BYK</label>
                                <select class="form-select" name="byk_category" id="editBYK">
                                    <option value="">BYK Seçin</option>
                                    <option value="AT">AT - Ana Teşkilat</option>
                                    <option value="KT">KT - Kadınlar Teşkilatı</option>
                                    <option value="KGT">KGT - Kadınlar Gençlik Teşkilatı</option>
                                    <option value="GT">GT - Gençlik Teşkilatı</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rol</label>
                                <select class="form-select" name="role" id="editRole" required>
                                    <option value="superadmin">Superadmin</option>
                                    <option value="manager">Manager</option>
                                    <option value="member">Üye</option>
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
            // Show all rows for debug
            $('tbody tr').show();
            
            // Mobile sidebar toggle
            $('.navbar-toggler').click(function() {
                $('.sidebar').toggleClass('show');
            });
            
            // Otomatik kullanıcı adı oluşturma
            $('#firstName, #lastName').on('input', function() {
                generateUsername();
            });
            
            // Positions verilerini yükle
            loadPositions();
        });
        
        // Positions verilerini yükle
        function loadPositions() {
            fetch('api/code_list_api.php?action=get_items&category=positions')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const positionSelect = document.getElementById('position');
                    const editPositionSelect = document.getElementById('editPosition');
                    
                    // Mevcut option'ları temizle (ilk option hariç)
                    positionSelect.innerHTML = '<option value="">Görev Seçin</option>';
                    editPositionSelect.innerHTML = '<option value="">Görev Seçin</option>';
                    
                    // Duplicate'leri temizle - sadece unique name'leri al
                    const uniquePositions = [];
                    const seenNames = new Set();
                    
                    data.data.forEach(position => {
                        if (!seenNames.has(position.name)) {
                            seenNames.add(position.name);
                            uniquePositions.push(position);
                        }
                    });
                    
                    console.log('Toplam positions:', data.data.length);
                    console.log('Unique positions:', uniquePositions.length);
                    
                    // Unique positions verilerini ekle
                    uniquePositions.forEach(position => {
                        const option1 = new Option(position.name, position.name);
                        const option2 = new Option(position.name, position.name);
                        positionSelect.add(option1);
                        editPositionSelect.add(option2);
                    });
                } else {
                    console.error('Positions yüklenemedi:', data.message);
                }
            })
            .catch(error => {
                console.error('Positions API hatası:', error);
            });
        }
        
        // Türkçe karakterleri dönüştürme fonksiyonu
        function convertTurkishChars(text) {
            const turkishChars = {
                'ü': 'ue', 'Ü': 'Ue',
                'ö': 'oe', 'Ö': 'Oe', 
                'ğ': 'g', 'Ğ': 'G',
                'ş': 's', 'Ş': 'S',
                'ç': 'c', 'Ç': 'C',
                'ı': 'i', 'İ': 'I'
            };
            
            return text.replace(/[üÜöÖğĞşŞçÇıİ]/g, function(match) {
                return turkishChars[match];
            });
        }
        
        // Otomatik kullanıcı adı oluşturma
        function generateUsername() {
            const firstName = $('#firstName').val().trim();
            const lastName = $('#lastName').val().trim();
            
            if (firstName && lastName) {
                // Türkçe karakterleri dönüştür ve küçük harfe çevir
                const convertedFirst = convertTurkishChars(firstName).toLowerCase();
                const convertedLast = convertTurkishChars(lastName).toLowerCase();
                
                // Boşlukları ve özel karakterleri temizle
                const cleanFirst = convertedFirst.replace(/[^a-z]/g, '');
                const cleanLast = convertedLast.replace(/[^a-z]/g, '');
                
                // Kullanıcı adını oluştur: isim.soyisim
                const username = cleanFirst + '.' + cleanLast;
                $('#username').val(username);
            }
        }
        
        // User management functions
        function saveUser() {
            const form = document.getElementById('addUserForm');
            const formData = new FormData(form);
            
            // Form verilerini JSON'a çevir
            const userData = Object.fromEntries(formData);
            
            // BYK alanını düzelt
            if (userData.byk_category) {
                userData.byk = userData.byk_category;
                delete userData.byk_category;
            }
            
            fetch('add_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(userData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Kullanıcı başarıyla eklendi!', 'success');
                    if (data.debug) {
                        console.log('BYK Debug Info:', data.debug);
                        showAlert('Debug: BYK=' + data.debug.byk_input + ', ID=' + data.debug.byk_category_id, 'info');
                    }
                    $('#addUserModal').modal('hide');
                    location.reload();
                } else {
                    showAlert(data.message || 'Bir hata oluştu!', 'danger');
                    if (data.debug) {
                        console.log('BYK Debug Error:', data.debug);
                        showAlert('Debug Error: BYK=' + data.debug.byk_input + ', ID=' + data.debug.byk_category_id, 'warning');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Bir hata oluştu!', 'danger');
            });
        }
        
        function editUser(username) {
            // Kullanıcı verilerini veritabanından çek
            fetch('get_user.php?username=' + encodeURIComponent(username))
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Form alanlarını doldur
                    document.getElementById('editUsername').value = data.user.username;
                    document.getElementById('editFirstName').value = data.user.first_name || '';
                    document.getElementById('editLastName').value = data.user.last_name || '';
                    document.getElementById('editEmail').value = data.user.email;
                    document.getElementById('editPosition').value = data.user.position || '';
                    document.getElementById('editBYK').value = data.user.byk_category || '';
                    document.getElementById('editRole').value = data.user.role;
                    document.getElementById('editStatus').value = data.user.status || 'active';
                    
                    // Modal'ı göster
                    $('#editUserModal').modal('show');
                } else {
                    showAlert('Kullanıcı bulunamadı!', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Bir hata oluştu!', 'danger');
            });
        }
        
        function updateUser() {
            const form = document.getElementById('editUserForm');
            const formData = new FormData(form);
            
            // Form verilerini JSON'a çevir
            const userData = Object.fromEntries(formData);
            
            // BYK alanını düzelt
            if (userData.byk_category) {
                userData.byk = userData.byk_category;
                delete userData.byk_category;
            }
            
            // Username alanını ekle (güncelleme için gerekli)
            userData.username = document.getElementById('editUsername').value;
            
            console.log('Update userData:', userData);
            
            fetch('update_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(userData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Kullanıcı başarıyla güncellendi!', 'success');
                    if (data.debug) {
                        console.log('BYK Debug Info:', data.debug);
                        showAlert('Debug: BYK=' + data.debug.byk_input + ', ID=' + data.debug.byk_category_id, 'info');
                    }
                    $('#editUserModal').modal('hide');
                    location.reload();
                } else {
                    showAlert(data.message || 'Bir hata oluştu!', 'danger');
                    if (data.debug) {
                        console.log('BYK Debug Error:', data.debug);
                        showAlert('Debug Error: BYK=' + data.debug.byk_input + ', ID=' + data.debug.byk_category_id, 'warning');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Bir hata oluştu!', 'danger');
            });
        }
        
        function deleteUser(username) {
            if (confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?')) {
                fetch('delete_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({username: username})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Kullanıcı başarıyla silindi!', 'success');
                        location.reload();
                    } else {
                        showAlert(data.message || 'Bir hata oluştu!', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Bir hata oluştu!', 'danger');
                });
            }
        }
        
        // Modern JavaScript fonksiyonları
        function clearFilters() {
            document.querySelector('input[placeholder="Kullanıcı ara..."]').value = '';
            document.getElementById('bykFilter').value = '';
            document.getElementById('roleFilter').value = '';
            // Tüm satırları göster
            $('tbody tr').show();
        }
        
        function exportUsers() {
            showAlert('Dışa aktarma özelliği yakında eklenecek!', 'info');
        }
        
        function refreshUsers() {
            location.reload();
        }
        
        // Filtreleme fonksiyonları
        function filterUsers() {
            const searchTerm = document.querySelector('input[placeholder="Kullanıcı ara..."]').value.toLowerCase();
            const bykFilter = document.getElementById('bykFilter').value;
            const roleFilter = document.getElementById('roleFilter').value;
            
            $('tbody tr').each(function() {
                const row = $(this);
                const userName = row.find('td:first').text().toLowerCase();
                const userEmail = row.find('td:nth-child(2)').text().toLowerCase();
                const userBYK = row.find('td:nth-child(3)').text();
                const userRole = row.find('td:nth-child(4)').text();
                
                let show = true;
                
                // Arama filtresi
                if (searchTerm && !userName.includes(searchTerm) && !userEmail.includes(searchTerm)) {
                    show = false;
                }
                
                // BYK filtresi
                if (bykFilter && !userBYK.includes(bykFilter)) {
                    show = false;
                }
                
                // Rol filtresi
                if (roleFilter && !userRole.toLowerCase().includes(roleFilter.toLowerCase())) {
                    show = false;
                }
                
                if (show) {
                    row.show();
                } else {
                    row.hide();
                }
            });
        }
        
        // Event listeners
        $(document).ready(function() {
            // Show all rows for debug
            $('tbody tr').show();
            
            // Mobile sidebar toggle
            $('.navbar-toggler').click(function() {
                $('.sidebar').toggleClass('show');
            });
            
            // Otomatik kullanıcı adı oluşturma
            $('#firstName, #lastName').on('input', function() {
                generateUsername();
            });
            
            // Positions verilerini yükle
            loadPositions();
            
            // Filtreleme event listeners
            $('input[placeholder="Kullanıcı ara..."]').on('input', filterUsers);
            $('#bykFilter, #roleFilter').on('change', filterUsers);
        });
        
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
                window.location.href = '../logout.php';
            }
        }
    </script>
</body>
</html>