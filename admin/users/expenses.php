<?php
require_once 'auth.php';

// Session kontrolü - sadece superadmin giriş yapabilir
SessionManager::requireRole('superadmin');

$currentUser = SessionManager::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIF Otomasyon - İade Talepleri</title>
    
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
                    <h1>İade Talepleri</h1>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                        <i class="fas fa-plus"></i> İade Talebi Ekle
                    </button>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Expense Statistics -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-primary">24</h3>
                            <p class="text-muted mb-0">Toplam Talep</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-warning">8</h3>
                            <p class="text-muted mb-0">Bekleyen</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-success">14</h3>
                            <p class="text-muted mb-0">Onaylanan</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <h3 class="text-danger">2</h3>
                            <p class="text-muted mb-0">Reddedilen</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expense Categories -->
            <div class="row mb-4">
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="expense-card">
                        <div class="card-body text-center">
                            <div class="expense-icon transport">
                                <i class="fas fa-car"></i>
                            </div>
                            <h6>Ulaşım</h6>
                            <p class="text-muted mb-0">₺1,250</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="expense-card">
                        <div class="card-body text-center">
                            <div class="expense-icon food">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <h6>Yemek</h6>
                            <p class="text-muted mb-0">₺850</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="expense-card">
                        <div class="card-body text-center">
                            <div class="expense-icon accommodation">
                                <i class="fas fa-bed"></i>
                            </div>
                            <h6>Konaklama</h6>
                            <p class="text-muted mb-0">₺2,100</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="expense-card">
                        <div class="card-body text-center">
                            <div class="expense-icon communication">
                                <i class="fas fa-phone"></i>
                            </div>
                            <h6>İletişim</h6>
                            <p class="text-muted mb-0">₺320</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="expense-card">
                        <div class="card-body text-center">
                            <div class="expense-icon supplies">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <h6>Malzeme</h6>
                            <p class="text-muted mb-0">₺480</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expense Management -->
            <div class="page-card">
                <div class="card-header">
                    <h5><i class="fas fa-file-invoice-dollar"></i> İade Talepleri</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-card">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Talep No</th>
                                    <th>Kullanıcı</th>
                                    <th>Kategori</th>
                                    <th>Tutar</th>
                                    <th>Tarih</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="fw-bold">#EXP001</div>
                                        <small class="text-muted">Ulaşım</small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                                AY
                                            </div>
                                            <div>
                                                <div class="fw-bold">Ahmet Yılmaz</div>
                                                <small class="text-muted">IT Departmanı</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-info">Ulaşım</span></td>
                                    <td>
                                        <div class="fw-bold">₺150</div>
                                        <small class="text-muted">Taksi ücreti</small>
                                    </td>
                                    <td>20.01.2024</td>
                                    <td><span class="status-badge status-pending">Beklemede</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-success me-1" onclick="approveExpense('EXP001')">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger me-1" onclick="rejectExpense('EXP001')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="fw-bold">#EXP002</div>
                                        <small class="text-muted">Yemek</small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                                FD
                                            </div>
                                            <div>
                                                <div class="fw-bold">Fatma Demir</div>
                                                <small class="text-muted">İnsan Kaynakları</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-warning">Yemek</span></td>
                                    <td>
                                        <div class="fw-bold">₺85</div>
                                        <small class="text-muted">İş yemeği</small>
                                    </td>
                                    <td>19.01.2024</td>
                                    <td><span class="status-badge status-approved">Onaylandı</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="fw-bold">#EXP003</div>
                                        <small class="text-muted">Konaklama</small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                                MK
                                            </div>
                                            <div>
                                                <div class="fw-bold">Mehmet Kaya</div>
                                                <small class="text-muted">Satış Departmanı</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-primary">Konaklama</span></td>
                                    <td>
                                        <div class="fw-bold">₺200</div>
                                        <small class="text-muted">Otel ücreti</small>
                                    </td>
                                    <td>18.01.2024</td>
                                    <td><span class="status-badge status-processing">İşlemde</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Expense Modal -->
    <div class="modal fade" id="addExpenseModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni İade Talebi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addExpenseForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kategori</label>
                                <select class="form-select" id="expenseCategory" required>
                                    <option value="">Seçiniz</option>
                                    <option value="transport">Ulaşım</option>
                                    <option value="food">Yemek</option>
                                    <option value="accommodation">Konaklama</option>
                                    <option value="communication">İletişim</option>
                                    <option value="supplies">Malzeme</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tutar</label>
                                <input type="number" class="form-control" id="expenseAmount" step="0.01" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tarih</label>
                                <input type="date" class="form-control" id="expenseDate" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fatura No</label>
                                <input type="text" class="form-control" id="expenseInvoice">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Açıklama</label>
                                <textarea class="form-control" id="expenseDescription" rows="3" required></textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Fatura/Makbuz</label>
                                <input type="file" class="form-control" id="expenseReceipt" accept="image/*,.pdf">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" id="saveExpense">Kaydet</button>
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
            
            // Save Expense
            $('#saveExpense').click(function() {
                const expenseCategory = $('#expenseCategory').val();
                const expenseAmount = $('#expenseAmount').val();
                const expenseDate = $('#expenseDate').val();
                const expenseDescription = $('#expenseDescription').val();
                
                if (expenseCategory && expenseAmount && expenseDate && expenseDescription) {
                    $('#addExpenseModal').modal('hide');
                    $('#addExpenseForm')[0].reset();
                    showNotification('İade talebi başarıyla eklendi!', 'success');
                } else {
                    showNotification('Lütfen tüm zorunlu alanları doldurun!', 'warning');
                }
            });
            
            // Approve Expense
            window.approveExpense = function(expenseId) {
                if (confirm('Bu iade talebini onaylamak istediğinizden emin misiniz?')) {
                    showNotification(`İade talebi ${expenseId} onaylandı!`, 'success');
                }
            };
            
            // Reject Expense
            window.rejectExpense = function(expenseId) {
                if (confirm('Bu iade talebini reddetmek istediğinizden emin misiniz?')) {
                    showNotification(`İade talebi ${expenseId} reddedildi!`, 'warning');
                }
            };
            
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
            
            // Mobile sidebar toggle
            $('.navbar-toggler').click(function() {
                $('.sidebar').toggleClass('show');
            });
        });
    </script>
</body>
</html>