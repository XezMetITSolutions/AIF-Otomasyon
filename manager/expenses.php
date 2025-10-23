
<?php
require_once 'auth.php';
require_once 'includes/database.php';

// Veritabanı bağlantısı
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    // Hata sayfası göster
    ?>
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Veritabanı Hatası - AIF Otomasyon</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">
                            <h4 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Veritabanı Bağlantı Hatası</h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-danger">
                                <h5>Hata Detayı:</h5>
                                <p><?php echo htmlspecialchars($e->getMessage()); ?></p>
                            </div>
                            
                            <h5>Çözüm Önerileri:</h5>
                            <ol>
                                <li><strong>Veritabanı Bilgilerini Kontrol Edin:</strong>
                                    <ul>
                                        <li>Host: localhost</li>
                                        <li>Veritabanı Adı: d0451622</li>
                                        <li>Kullanıcı Adı: d0451622</li>
                                        <li>Şifre: 01528797Mb##</li>
                                    </ul>
                                </li>
                                <li><strong>cPanel'de Veritabanı Oluşturun:</strong>
                                    <ul>
                                        <li>cPanel → MySQL Databases</li>
                                        <li>Yeni veritabanı oluşturun</li>
                                        <li>Kullanıcı ekleyin ve yetkilendirin</li>
                                    </ul>
                                </li>
                                <li><strong>Dosya İzinlerini Kontrol Edin:</strong>
                                    <ul>
                                        <li>config/database.php dosyası okunabilir olmalı</li>
                                        <li>Log dosyaları yazılabilir olmalı</li>
                                    </ul>
                                </li>
                            </ol>
                            
                            <div class="mt-4">
                                <h6>Manuel SQL Kurulum:</h6>
                                <p>Eğer veritabanı mevcutsa, aşağıdaki SQL kodunu phpMyAdmin'de çalıştırın:</p>
                                <pre class="bg-light p-3 rounded"><code>-- Veritabanı zaten mevcut: d0451622
-- Sadece tabloları oluşturun:

USE d0451622;

CREATE TABLE expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    isim VARCHAR(100) NOT NULL,
    soyisim VARCHAR(100) NOT NULL,
    iban VARCHAR(34) NOT NULL,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('pending', 'paid', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE expense_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    expense_id INT NOT NULL,
    tarih DATE NOT NULL,
    region VARCHAR(10),
    birim VARCHAR(50),
    birim_label VARCHAR(100),
    gider_turu VARCHAR(50),
    gider_turu_label VARCHAR(100),
    tutar DECIMAL(10,2) NOT NULL DEFAULT 0,
    aciklama TEXT,
    attachments JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expense_id) REFERENCES expenses(id) ON DELETE CASCADE
);

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(200) NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    department VARCHAR(50),
    unit VARCHAR(50),
    byk VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);</code></pre>
                            </div>
                            
                            <div class="mt-3">
                                <button class="btn btn-primary" onclick="location.reload()">
                                    <i class="fas fa-refresh me-2"></i>Tekrar Dene
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Giderleri getir
$sql = "
    SELECT e.id, e.isim, e.soyisim, e.iban, e.total, e.status, e.created_at,
           u.department, u.unit, u.byk
    FROM expenses e
    LEFT JOIN users u ON e.user_id = u.id
    ORDER BY e.created_at DESC
    LIMIT 50
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$expenses = $stmt->fetchAll();

// İstatistikleri hesapla
$stats_sql = "
    SELECT 
        COUNT(*) as total_count,
        SUM(total) as total_amount,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
    FROM expenses
";

$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute();
$stats = $stats_stmt->fetch();

// Durum metinleri
$status_texts = [
    'pending' => 'Ödeme Bekliyor',
    'paid' => 'Ödendi',
    'rejected' => 'Reddedildi'
];

// Kullanıcıları getir (filtreleme için)
$users_sql = "SELECT DISTINCT CONCAT(isim, ' ', soyisim) as full_name FROM expenses ORDER BY full_name";
$users_stmt = $pdo->prepare($users_sql);
$users_stmt->execute();
$users = $users_stmt->fetchAll(PDO::FETCH_COLUMN);

// Birimleri getir
$units_sql = "SELECT DISTINCT unit FROM users WHERE unit IS NOT NULL ORDER BY unit";
$units_stmt = $pdo->prepare($units_sql);
$units_stmt->execute();
$units = $units_stmt->fetchAll(PDO::FETCH_COLUMN);

// BYK'ları getir
$byks_sql = "SELECT DISTINCT byk FROM users WHERE byk IS NOT NULL ORDER BY byk";
$byks_stmt = $pdo->prepare($byks_sql);
$byks_stmt->execute();
$byks = $byks_stmt->fetchAll(PDO::FETCH_COLUMN);

// Login kontrolü kaldırıldı - direkt erişim
$currentUser = SessionManager::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>AIF Otomasyon - Gider Yönetimi</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- pdfMake -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <!-- PDF Generation Script -->
    <script src="generate_pdf_pdfmake.js"></script>
    
    <style>
        <?php include 'includes/styles.php'; ?>
        
        /* Gider Panosu Tasarımı */
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-content {
            padding: 20px;
        }

        /* Ana Başlık */
        .main-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }

        /* Ana Tablo */
        .main-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .table-header {
            background: var(--primary-color);
            color: white;
            padding: 15px 20px;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .table th {
            background: var(--primary-color);
            color: white;
            font-weight: 600;
            border: none;
            padding: 12px;
        }

        .table td {
            vertical-align: middle;
            padding: 12px;
            border-color: #e9ecef;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Durum Butonları */
        .status-btn {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            border: none;
        }

        .status-pending {
            background-color: #007bff;
            color: white;
        }

        .status-paid {
            background-color: var(--primary-color);
            color: white;
        }

        .status-rejected {
            background-color: #dc3545;
            color: white;
        }

        /* İşlem Butonları */
        .action-btn {
            padding: 4px 8px;
            margin: 0 2px;
            border-radius: 4px;
            font-size: 0.8rem;
            border: none;
        }

        .btn-paid { background-color: var(--primary-color); color: white; }
        .btn-edit { background-color: #ffc107; color: #000; }
        .btn-delete { background-color: #dc3545; color: white; }
        .btn-pdf { background-color: #6f42c1; color: white; }

        /* Filtreleme Alanı */
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }

        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
        }

        .filter-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }



        /* Başarı Mesajları */
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .alert-success .btn-close {
            color: #155724;
        }

        /* Boş Tablo Mesajı */
        .table tbody tr td.text-center {
            font-style: italic;
            color: #6c757d;
        }

        .table tbody tr td.text-center i {
            opacity: 0.5;
        }

        /* Modal Stilleri */
        .modal-xl {
            max-width: 1200px;
        }

        .modal-header {
            border-radius: 10px 10px 0 0;
        }

        .modal-content {
            border-radius: 10px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }

        .expense-item {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6 !important;
        }

        .expense-item .form-control[readonly] {
            background-color: #e9ecef;
            color: #6c757d;
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 10px;
            }
            
            .filter-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .filter-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Ana Başlık -->
        <h1 class="main-title">Gider Panosu</h1>

        <!-- Ana Gider Tablosu -->
        <div class="main-table">
            <div class="table-header">Gider Listesi</div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Gider No</th>
                            <th>İsim Soyisim</th>
                            <th>IBAN</th>
                            <th>Toplam (€)</th>
                            <th>BYK</th>
                            <th>Birim</th>
                            <th>Gönderim Tarihi</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                            <th>PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expenses)): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                    Henüz gider kaydı bulunmuyor
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($expenses as $expense): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($expense['id']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($expense['isim'] . ' ' . $expense['soyisim']); ?></td>
                                    <td><?php echo htmlspecialchars($expense['iban']); ?></td>
                                    <td><strong><?php echo number_format($expense['total'], 2); ?> €</strong></td>
                                    <td><?php echo htmlspecialchars($expense['byk'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($expense['unit'] ?: '-'); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($expense['created_at'])); ?></td>
                                    <td>
                                        <span class="status-btn status-<?php echo $expense['status']; ?>">
                                            <?php echo $status_texts[$expense['status']] ?? 'Bilinmiyor'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="action-btn btn-paid" onclick="markAsPaid(<?php echo $expense['id']; ?>)">Ödendi</button>
                                        <button class="action-btn btn-edit" onclick="editExpense(<?php echo $expense['id']; ?>)">Düzenle</button>
                                        <button class="action-btn btn-delete" onclick="deleteExpense(<?php echo $expense['id']; ?>)">Sil</button>
                                    </td>
                                    <td>
                                        <button class="action-btn btn-pdf" onclick="viewPDF(<?php echo $expense['id']; ?>)">PDF Görüntüle</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
                    </div>

        <!-- Filtreleme Alanı -->
        <div class="filter-section">
            <div class="filter-row">
                <div class="filter-group">
                    <label>Yıl</label>
                    <select>
                        <option>Hepsi</option>
                        <option>2025</option>
                        <option>2024</option>
                    </select>
                    </div>
                <div class="filter-group">
                    <label>Ay</label>
                    <select>
                        <option>Hepsi</option>
                        <option>Ocak</option>
                        <option>Şubat</option>
                        <option>Mart</option>
                        <option>Nisan</option>
                        <option>Mayıs</option>
                        <option>Haziran</option>
                        <option>Temmuz</option>
                        <option>Ağustos</option>
                        <option>Eylül</option>
                        <option>Ekim</option>
                        <option>Kasım</option>
                        <option>Aralık</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Kişi</label>
                    <select>
                        <option>Hepsi</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo htmlspecialchars($user); ?>"><?php echo htmlspecialchars($user); ?></option>
                        <?php endforeach; ?>
                    </select>
            </div>
                <div class="filter-group">
                    <label>Birim</label>
                    <select>
                        <option>Hepsi</option>
                        <?php foreach ($units as $unit): ?>
                            <option value="<?php echo htmlspecialchars($unit); ?>"><?php echo htmlspecialchars($unit); ?></option>
                        <?php endforeach; ?>
                    </select>
        </div>
                <div class="filter-group">
                    <label>BYK</label>
                    <select>
                        <option>Hepsi</option>
                        <?php foreach ($byks as $byk): ?>
                            <option value="<?php echo htmlspecialchars($byk); ?>"><?php echo htmlspecialchars($byk); ?></option>
                        <?php endforeach; ?>
                    </select>
                    </div>
                <div class="filter-group">
                    <label>Durum</label>
                    <select>
                        <option>Hepsi</option>
                        <option>Ödeme Bekliyor</option>
                        <option>Ödendi</option>
                        <option>Reddedildi</option>
                    </select>
                </div>
            </div>
            <div class="filter-actions">
                <button class="btn btn-success" onclick="applyFilters()">Filtrele</button>
                <button class="btn btn-primary" onclick="exportCSV()">CSV Dışa Aktar</button>
                    </div>
            </div>


        </div>

    <!-- Düzenleme Modal -->
    <div class="modal fade" id="editExpenseModal" tabindex="-1" aria-labelledby="editExpenseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--primary-color); color: white;">
                    <h5 class="modal-title" id="editExpenseModalLabel">
                        <i class="fas fa-edit me-2"></i>Gider Düzenle
            </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                <div class="modal-body">
                    <form id="editExpenseForm">
                        <input type="hidden" id="edit_expense_id" name="expense_id">
                        
                        <!-- Kişisel Bilgiler -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-user me-2"></i>Kişisel Bilgiler</h6>
                                    </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="edit_isim" class="form-label">İsim</label>
                                        <input type="text" class="form-control" id="edit_isim" name="isim" required>
                                </div>
                                    <div class="col-md-6">
                                        <label for="edit_soyisim" class="form-label">Soyisim</label>
                                        <input type="text" class="form-control" id="edit_soyisim" name="soyisim" required>
                                    </div>
                                    </div>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <label for="edit_iban" class="form-label">IBAN</label>
                                        <input type="text" class="form-control" id="edit_iban" name="iban" required>
                                </div>
                                    <div class="col-md-6">
                                        <label for="edit_total" class="form-label">Toplam Tutar (€)</label>
                                        <input type="number" class="form-control" id="edit_total" name="total" step="0.01" required>
                                    </div>
                                    </div>
            </div>
        </div>

                        <!-- Gider Kalemleri -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-list me-2"></i>Gider Kalemleri</h6>
                </div>
                            <div class="card-body">
                                <div id="edit_expense_items">
                                    <!-- Gider kalemleri buraya yüklenecek -->
                </div>
                </div>
                </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>İptal
                    </button>
                    <button type="button" class="btn btn-primary" onclick="saveExpenseChanges()">
                        <i class="fas fa-save me-2"></i>Kaydet
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>

        // İşlem fonksiyonları
        function markAsPaid(id) {
            if (confirm('Bu gideri ödendi olarak işaretlemek istediğinizden emin misiniz?')) {
                // AJAX çağrısı ile durumu güncelle
                $.ajax({
                    url: 'update_expense_status.php',
                    method: 'POST',
                    data: {
                        expense_id: id,
                        status: 'paid'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Durum butonunu güncelle
                            updateStatusButton(id, 'paid', 'Ödendi');
                            
                            // Başarı mesajı
                            showSuccessMessage('Gider ödendi olarak işaretlendi!');
                        } else {
                            alert('Hata: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Sunucu hatası oluştu!');
                    }
                });
            }
        }

        // Durum butonunu güncelle
        function updateStatusButton(expenseId, status, statusText) {
            const tableRows = document.querySelectorAll('.table tbody tr');
            tableRows.forEach(row => {
                const idCell = row.querySelector('td:first-child strong');
                if (idCell && idCell.textContent.trim() === expenseId.toString()) {
                    const statusCell = row.querySelector('td:nth-child(8) .status-btn');
                    if (statusCell) {
                        // Eski sınıfları kaldır
                        statusCell.classList.remove('status-pending', 'status-paid', 'status-rejected');
                        
                        // Yeni sınıfı ekle
                        statusCell.classList.add(`status-${status}`);
                        
                        // Metni güncelle
                        statusCell.textContent = statusText;
                    }
                }
            });
        }

        function editExpense(id) {
            // Modal'ı aç
            $('#editExpenseModal').modal('show');
            
            // Gider verilerini yükle
            loadExpenseData(id);
        }

        // Gider verilerini modal'a yükle
        async function loadExpenseData(expenseId) {
            try {
                const response = await $.ajax({
                    url: 'get_expense_data.php',
                    method: 'GET',
                    data: { expense_id: expenseId }
                });
                
                if (response.success) {
                    const data = response.data;
                    
                    // Modal'daki form alanlarını doldur
                    $('#edit_expense_id').val(data.id);
                    $('#edit_isim').val(data.isim);
                    $('#edit_soyisim').val(data.soyisim);
                    $('#edit_iban').val(data.iban);
                    $('#edit_total').val(data.total);
                    
                    // Gider kalemlerini yükle
                    loadExpenseItems(data.items || []);
                    
                } else {
                    alert('Gider verileri yüklenemedi: ' + response.message);
                }
            } catch (error) {
                console.error('Veri yükleme hatası:', error);
                alert('Veri yükleme hatası oluştu!');
            }
        }

        // Gider kalemlerini modal'a yükle
        function loadExpenseItems(items) {
            const itemsContainer = $('#edit_expense_items');
            itemsContainer.empty();
            
            if (items.length === 0) {
                itemsContainer.html('<div class="text-center text-muted py-3">Henüz gider kalemi eklenmemiş</div>');
                return;
            }
            
            items.forEach((item, index) => {
                const itemHtml = `
                    <div class="expense-item border rounded p-3 mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Tarih</label>
                                <input type="date" class="form-control" name="edit_tarih[]" value="${item.tarih}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">BYK</label>
                                <input type="text" class="form-control" name="edit_region[]" value="${item.region || ''}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Birim</label>
                                <input type="text" class="form-control" name="edit_birim[]" value="${item.birim_label || item.birim || ''}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Gider Türü</label>
                                <input type="text" class="form-control" name="edit_gider_turu[]" value="${item.gider_turu_label || item.gider_turu || ''}" readonly>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-6">
                                <label class="form-label">Tutar (€)</label>
                                <input type="number" class="form-control" name="edit_tutar[]" value="${item.tutar}" step="0.01" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Açıklama</label>
                                <input type="text" class="form-control" name="edit_aciklama[]" value="${item.aciklama || ''}" readonly>
                            </div>
                        </div>
                    </div>
                `;
                itemsContainer.append(itemHtml);
            });
        }

        function deleteExpense(id) {
            if (confirm('Bu gideri silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!')) {
                // AJAX çağrısı ile sil
                $.ajax({
                    url: 'delete_expense.php',
                    method: 'POST',
                    data: {
                        expense_id: id
                    },
                    success: function(response) {
                        if (response.success) {
                            // Tablodan satırı kaldır
                            removeTableRow(id);
                            
                            // Başarı mesajı
                            showSuccessMessage('Gider başarıyla silindi!');
                        } else {
                            alert('Hata: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Sunucu hatası oluştu!');
                    }
                });
            }
        }

        // Tablodan satırı kaldır
        function removeTableRow(expenseId) {
            const tableRows = document.querySelectorAll('.table tbody tr');
            tableRows.forEach(row => {
                const idCell = row.querySelector('td:first-child strong');
                if (idCell && idCell.textContent.trim() === expenseId.toString()) {
                    row.remove();
                }
            });
            
            // Eğer hiç gider kalmadıysa
            const remainingRows = document.querySelectorAll('.table tbody tr').length;
            if (remainingRows === 0) {
                const tbody = document.querySelector('.table tbody');
                tbody.innerHTML = `
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2"></i><br>
                            Henüz gider kaydı bulunmuyor
                        </td>
                    </tr>
                `;
            }
        }


        // Başarı mesajı göster
        function showSuccessMessage(message) {
            // Mevcut alert'leri temizle
            $('.alert-success').remove();
            
            // Yeni başarı mesajı ekle
            const alert = $(`
                <div class="alert alert-success alert-dismissible fade show position-fixed" 
                     style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                    <i class="fas fa-check-circle me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);
            
            $('body').append(alert);
            
            // 3 saniye sonra otomatik kapat
            setTimeout(function() {
                alert.alert('close');
            }, 3000);
        }

        async function viewPDF(id) {
            try {
                // Önce gider verilerini al
                const expenseData = await getExpenseData(id);
                
                if (!expenseData) {
                    alert('Gider verileri bulunamadı!');
                    return;
                }

                // PDF oluştur
                const pdfBlob = await generateNewPDFFromDashboard(expenseData);
                
                // PDF'i yeni sekmede aç
                const url = URL.createObjectURL(pdfBlob);
                window.open(url, '_blank');
                
                // URL'yi temizle
                setTimeout(() => URL.revokeObjectURL(url), 1000);
                
            } catch (error) {
                console.error('PDF görüntüleme hatası:', error);
                alert('PDF görüntülenirken hata oluştu: ' + error.message);
            }
        }

        // Gider verilerini getir
        function getExpenseData(id) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: 'get_expense_data.php',
                    method: 'GET',
                    data: { expense_id: id },
                    success: function(response) {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(new Error(response.message));
                        }
                    },
                    error: function() {
                        reject(new Error('Sunucu hatası oluştu!'));
                    }
                });
            });
        }

        function applyFilters() {
            const filters = {
                year: document.querySelector('select:nth-of-type(1)').value,
                month: document.querySelector('select:nth-of-type(2)').value,
                person: document.querySelector('select:nth-of-type(3)').value,
                unit: document.querySelector('select:nth-of-type(4)').value,
                byk: document.querySelector('select:nth-of-type(5)').value,
                status: document.querySelector('select:nth-of-type(6)').value
            };

            // AJAX çağrısı ile filtreleme
            $.ajax({
                url: 'filter_expenses.php',
                method: 'POST',
                data: filters,
                success: function(response) {
                    if (response.success) {
                        updateTable(response.data);
                    } else {
                        alert('Filtreleme hatası: ' + response.message);
                    }
                },
                error: function() {
                    alert('Sunucu hatası oluştu!');
                }
            });
        }

        function exportCSV() {
            const filters = {
                year: document.querySelector('select:nth-of-type(1)').value,
                month: document.querySelector('select:nth-of-type(2)').value,
                person: document.querySelector('select:nth-of-type(3)').value,
                unit: document.querySelector('select:nth-of-type(4)').value,
                byk: document.querySelector('select:nth-of-type(5)').value,
                status: document.querySelector('select:nth-of-type(6)').value
            };

            // CSV export
            $.ajax({
                url: 'export_csv.php',
                method: 'POST',
                data: filters,
                success: function(response) {
                    if (response.success) {
                        // CSV dosyasını indir
                        const blob = new Blob([response.csv], { type: 'text/csv' });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `Giderler_${new Date().toISOString().split('T')[0]}.csv`;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                    } else {
                        alert('CSV export hatası: ' + response.message);
                    }
                },
                error: function() {
                    alert('Sunucu hatası oluştu!');
                }
            });
        }

        // Tabloyu güncelle
        function updateTable(data) {
            const tbody = document.querySelector('.table tbody');
            tbody.innerHTML = '';
            
            data.forEach(expense => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><strong>${expense.id}</strong></td>
                    <td>${expense.name}</td>
                    <td>${expense.iban}</td>
                    <td><strong>${expense.total} €</strong></td>
                    <td>${expense.byk}</td>
                    <td>${expense.unit}</td>
                    <td>${expense.date}</td>
                    <td><span class="status-btn status-${expense.status}">${expense.status_text}</span></td>
                    <td>
                        <button class="action-btn btn-paid" onclick="markAsPaid(${expense.id})">Ödendi</button>
                        <button class="action-btn btn-edit" onclick="editExpense(${expense.id})">Düzenle</button>
                        <button class="action-btn btn-delete" onclick="deleteExpense(${expense.id})">Sil</button>
                    </td>
                    <td>
                        <button class="action-btn btn-pdf" onclick="viewPDF(${expense.id})">PDF Görüntüle</button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }



        // Gider değişikliklerini kaydet
        function saveExpenseChanges() {
            const formData = {
                expense_id: $('#edit_expense_id').val(),
                isim: $('#edit_isim').val(),
                soyisim: $('#edit_soyisim').val(),
                iban: $('#edit_iban').val(),
                total: $('#edit_total').val()
            };
            
            // Validasyon
            if (!formData.expense_id || !formData.isim || !formData.soyisim || !formData.iban || !formData.total) {
                alert('Tüm alanlar doldurulmalıdır!');
                return;
            }
            
            // IBAN formatını kontrol et
            if (!/^AT\d{18}$/.test(formData.iban.replace(/\s/g, ''))) {
                alert('Geçersiz IBAN formatı!');
                return;
            }
            
            // AJAX ile kaydet
            $.ajax({
                url: 'update_expense.php',
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Modal'ı kapat
                        $('#editExpenseModal').modal('hide');
                        
                        // Başarı mesajı
                        showSuccessMessage('Gider başarıyla güncellendi!');
                        
                        // Sayfayı yenile
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                        
                    } else {
                        alert('Hata: ' + response.message);
                    }
                },
                error: function() {
                    alert('Sunucu hatası oluştu!');
                }
            });
        }

    </script>
</body>
</html>




