<?php
require_once 'auth.php';
require_once 'includes/user_manager_db.php';

// Login kontrolü - Geçici olarak devre dışı bırakıldı
// SessionManager::requireRole(['superadmin', 'manager']);
// $currentUser = SessionManager::getCurrentUser();

// Geçici kullanıcı verisi
$currentUser = [
    'id' => 1,
    'username' => 'admin',
    'full_name' => 'Admin User',
    'role' => 'manager',
    'email' => 'admin@example.com'
];

// Code List kategorileri
$codeCategories = [
    'byk' => [
        'name' => 'BYK Kategorileri',
        'table' => 'byk_categories',
        'fields' => ['code', 'name', 'description'],
        'display_fields' => ['code', 'name'],
        'color' => 'primary'
    ],
    'positions' => [
        'name' => 'Görevler',
        'table' => 'positions',
        'fields' => ['name', 'description', 'level'],
        'display_fields' => ['name'],
        'color' => 'success'
    ],
    'sub_units' => [
        'name' => 'Alt Birimler',
        'table' => 'sub_units',
        'fields' => ['name', 'description', 'byk_category_id'],
        'display_fields' => ['name'],
        'color' => 'info'
    ],
    'expense_types' => [
        'name' => 'Gider Türleri',
        'table' => 'expense_types',
        'fields' => ['name', 'description', 'category'],
        'display_fields' => ['name'],
        'color' => 'warning'
    ]
];

// Seçili kategori
$selectedCategory = $_GET['category'] ?? 'byk';
$currentCategory = $codeCategories[$selectedCategory] ?? $codeCategories['byk'];

// Veritabanından veri çekme
try {
    $db = Database::getInstance();
    $items = $db->fetchAll("SELECT * FROM {$currentCategory['table']} ORDER BY id DESC");
} catch (Exception $e) {
    // Hata durumunda demo veriler
    $items = getDemoData($selectedCategory);
}

function getDemoData($category) {
    switch ($category) {
        case 'byk':
            return [
                ['id' => 1, 'code' => 'AT', 'name' => 'Ana Teşkilat', 'description' => 'Ana teşkilat birimi'],
                ['id' => 2, 'code' => 'KT', 'name' => 'Kadınlar Teşkilatı', 'description' => 'Kadınlar teşkilatı'],
                ['id' => 3, 'code' => 'KGT', 'name' => 'Kadınlar Gençlik Teşkilatı', 'description' => 'Kadınlar gençlik teşkilatı'],
                ['id' => 4, 'code' => 'GT', 'name' => 'Gençlik Teşkilatı', 'description' => 'Gençlik teşkilatı']
            ];
        case 'positions':
            return [
                ['id' => 1, 'name' => 'Bölge Başkanı', 'description' => 'Bölge başkanı görevi', 'level' => '1'],
                ['id' => 2, 'name' => 'Teşkilatlanma Başkanı', 'description' => 'Teşkilatlanma başkanı görevi', 'level' => '2'],
                ['id' => 3, 'name' => 'Eğitim Başkanı', 'description' => 'Eğitim başkanı görevi', 'level' => '2'],
                ['id' => 4, 'name' => 'İrşad Başkanı', 'description' => 'İrşad başkanı görevi', 'level' => '2']
            ];
        case 'sub_units':
            return [
                ['id' => 1, 'name' => 'Yazılım Geliştirme', 'description' => 'Yazılım geliştirme birimi', 'byk_category_id' => '1'],
                ['id' => 2, 'name' => 'Sistem Yönetimi', 'description' => 'Sistem yönetimi birimi', 'byk_category_id' => '1'],
                ['id' => 3, 'name' => 'Ağ Güvenliği', 'description' => 'Ağ güvenliği birimi', 'byk_category_id' => '1'],
                ['id' => 4, 'name' => 'Veritabanı Yönetimi', 'description' => 'Veritabanı yönetimi birimi', 'byk_category_id' => '1']
            ];
        case 'expense_types':
            return [
                ['id' => 1, 'name' => 'Ulaşım', 'description' => 'Ulaşım giderleri', 'category' => 'Genel'],
                ['id' => 2, 'name' => 'Yemek', 'description' => 'Yemek giderleri', 'category' => 'Genel'],
                ['id' => 3, 'name' => 'Konaklama', 'description' => 'Konaklama giderleri', 'category' => 'Seyahat'],
                ['id' => 4, 'name' => 'Malzeme', 'description' => 'Malzeme giderleri', 'category' => 'Operasyon']
            ];
        default:
            return [];
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code List Yönetimi - AIF Otomasyon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        <?php include 'includes/styles.php'; ?>
        
        /* Code List Styles */
        .code-category-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: 100%;
        }
        
        .code-category-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-color: var(--primary-color);
        }
        
        .code-category-card.active {
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(0,160,133,0.25);
            background: linear-gradient(135deg, rgba(0,160,133,0.05), rgba(0,122,107,0.05));
        }
        
        .code-category-card .card-body {
            padding: 1rem;
        }
        
        .code-category-card i {
            transition: all 0.3s ease;
        }
        
        .code-category-card:hover i {
            transform: scale(1.1);
        }
        
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Code List Yönetimi</h1>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="showAddModal()">
                        <i class="fas fa-plus"></i> Yeni Ekle
                    </button>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Kategori Kartları -->
            <div class="row mb-4">
                <?php foreach ($codeCategories as $key => $category): ?>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card code-category-card <?php echo $selectedCategory === $key ? 'active' : ''; ?>" 
                         onclick="selectCategory('<?php echo $key; ?>')" style="cursor: pointer;">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="fas fa-<?php echo getCategoryIcon($key); ?> fa-2x text-<?php echo $category['color']; ?>"></i>
                            </div>
                            <h5 class="text-<?php echo $category['color']; ?>"><?php echo $category['name']; ?></h5>
                            <p class="text-muted mb-0"><?php echo count($items); ?> öğe</p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <!-- Code List Management -->
            <div class="page-card">
                <div class="card-header">
                    <h5><i class="fas fa-list"></i> <?php echo $currentCategory['name']; ?> Listesi</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <?php foreach ($currentCategory['display_fields'] as $field): ?>
                                    <th><?php echo ucfirst(str_replace('_', ' ', $field)); ?></th>
                                    <?php endforeach; ?>
                                    <th>Açıklama</th>
                                    <?php if (isset($currentCategory['fields']) && in_array('level', $currentCategory['fields'])): ?>
                                    <th>Seviye</th>
                                    <?php endif; ?>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <?php foreach ($currentCategory['display_fields'] as $field): ?>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($item[$field] ?? ''); ?></div>
                                    </td>
                                    <?php endforeach; ?>
                                    <td><?php echo htmlspecialchars($item['description'] ?? ''); ?></td>
                                    <?php if (isset($currentCategory['fields']) && in_array('level', $currentCategory['fields'])): ?>
                                    <td>
                                        <?php if (isset($item['level'])): ?>
                                        <span class="badge bg-<?php echo $item['level'] == '1' ? 'danger' : ($item['level'] == '2' ? 'warning' : 'secondary'); ?>">
                                            Seviye <?php echo $item['level']; ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1" onclick="editItem(<?php echo $item['id']; ?>)" title="Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteItem(<?php echo $item['id']; ?>)" title="Sil">
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
    
    <!-- Ekleme/Düzenleme Modal -->
    <div class="modal fade" id="itemModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Yeni Öğe Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="itemForm">
                    <div class="modal-body">
                        <input type="hidden" id="itemId" name="id">
                        <input type="hidden" id="category" name="category" value="<?php echo $selectedCategory; ?>">
                        
                        <?php foreach ($currentCategory['fields'] as $field): ?>
                        <div class="mb-3">
                            <label class="form-label"><?php echo ucfirst(str_replace('_', ' ', $field)); ?></label>
                            <?php if ($field === 'description'): ?>
                            <textarea class="form-control" name="<?php echo $field; ?>" id="<?php echo $field; ?>" rows="3"></textarea>
                            <?php elseif ($field === 'byk_category_id'): ?>
                            <select class="form-select" name="<?php echo $field; ?>" id="<?php echo $field; ?>">
                                <option value="">BYK Seçin</option>
                                <option value="1">AT - Ana Teşkilat</option>
                                <option value="2">KT - Kadınlar Teşkilatı</option>
                                <option value="3">KGT - Kadınlar Gençlik Teşkilatı</option>
                                <option value="4">GT - Gençlik Teşkilatı</option>
                            </select>
                            <?php elseif ($field === 'level'): ?>
                            <select class="form-select" name="<?php echo $field; ?>" id="<?php echo $field; ?>">
                                <option value="1">Seviye 1 (Üst)</option>
                                <option value="2">Seviye 2 (Orta)</option>
                                <option value="3">Seviye 3 (Alt)</option>
                            </select>
                            <?php elseif ($field === 'priority'): ?>
                            <select class="form-select" name="<?php echo $field; ?>" id="<?php echo $field; ?>">
                                <option value="Normal">Normal</option>
                                <option value="Yüksek">Yüksek</option>
                                <option value="Düşük">Düşük</option>
                            </select>
                            <?php elseif ($field === 'category'): ?>
                            <input type="text" class="form-control" name="<?php echo $field; ?>" id="<?php echo $field; ?>" placeholder="Kategori girin">
                            <?php else: ?>
                            <input type="text" class="form-control" name="<?php echo $field; ?>" id="<?php echo $field; ?>" required>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function selectCategory(category) {
            window.location.href = `?category=${category}`;
        }
        
        function refreshList() {
            location.reload();
        }
        
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Yeni Öğe Ekle';
            document.getElementById('itemForm').reset();
            document.getElementById('itemId').value = '';
            new bootstrap.Modal(document.getElementById('itemModal')).show();
        }
        
        function editItem(id) {
            // Öğe verilerini al ve formu doldur
            fetch(`api/code_list_api.php?action=get_item&id=${id}&category=<?php echo $selectedCategory; ?>`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const item = data.data;
                        document.getElementById('modalTitle').textContent = 'Öğe Düzenle';
                        document.getElementById('itemId').value = item.id;
                        
                        // Form alanlarını doldur
                        <?php foreach ($currentCategory['fields'] as $field): ?>
                        if (document.getElementById('<?php echo $field; ?>')) {
                            document.getElementById('<?php echo $field; ?>').value = item.<?php echo $field; ?> || '';
                        }
                        <?php endforeach; ?>
                        
                        new bootstrap.Modal(document.getElementById('itemModal')).show();
                    } else {
                        showAlert(data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Veri alınırken hata oluştu', 'danger');
                });
        }
        
        function deleteItem(id) {
            if (confirm('Bu öğeyi silmek istediğinizden emin misiniz?')) {
                fetch(`api/code_list_api.php?action=delete_item&id=${id}&category=<?php echo $selectedCategory; ?>`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        location.reload();
                    } else {
                        showAlert(data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Silme işleminde hata oluştu', 'danger');
                });
            }
        }
        
        // Form gönderimi
        document.getElementById('itemForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const isEdit = document.getElementById('itemId').value !== '';
            const action = isEdit ? 'update_item' : 'add_item';
            
            fetch('api/code_list_api.php?action=' + action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('itemModal')).hide();
                    location.reload();
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('İşlem sırasında hata oluştu', 'danger');
            });
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

<?php
function getCategoryIcon($category) {
    $icons = [
        'byk' => 'building',
        'positions' => 'user-tie',
        'sub_units' => 'sitemap',
        'expense_types' => 'receipt'
    ];
    return $icons[$category] ?? 'list';
}
?>
