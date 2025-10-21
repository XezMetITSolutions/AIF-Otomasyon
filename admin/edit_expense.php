<?php
require_once 'auth.php';

// Gider ID'sini al
$expense_id = $_GET['id'] ?? null;

if (!$expense_id) {
    header('Location: expenses.php');
    exit;
}

// Veritabanından gider verilerini al (örnek)
// $pdo = new PDO('mysql:host=localhost;dbname=aif_otomasyon', $username, $password);
// $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ?");
// $stmt->execute([$expense_id]);
// $expense = $stmt->fetch(PDO::FETCH_ASSOC);

// Şimdilik statik veri
$expense = [
    'id' => $expense_id,
    'isim' => 'Semra',
    'soyisim' => 'Yıldız',
    'iban' => 'AT862060200001095074',
    'total' => '96.98',
    'status' => 'pending'
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gider Düzenle - AIF Otomasyon</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background: #28a745;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
        }
        
        .btn-primary {
            background-color: #28a745;
            border-color: #28a745;
        }
        
        .btn-primary:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-edit me-2"></i>
                    Gider Düzenle - #<?php echo htmlspecialchars($expense['id']); ?>
                </h4>
            </div>
            <div class="card-body">
                <form id="editExpenseForm">
                    <input type="hidden" id="expense_id" value="<?php echo htmlspecialchars($expense['id']); ?>">
                    
                    <!-- Kişisel Bilgiler -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="isim" class="form-label">İsim</label>
                            <input type="text" class="form-control" id="isim" value="<?php echo htmlspecialchars($expense['isim']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="soyisim" class="form-label">Soyisim</label>
                            <input type="text" class="form-control" id="soyisim" value="<?php echo htmlspecialchars($expense['soyisim']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="iban" class="form-label">IBAN</label>
                            <input type="text" class="form-control" id="iban" value="<?php echo htmlspecialchars($expense['iban']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="total" class="form-label">Toplam Tutar (€)</label>
                            <input type="number" class="form-control" id="total" value="<?php echo htmlspecialchars($expense['total']); ?>" step="0.01" required>
                        </div>
                    </div>
                    
                    <!-- Durum -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="status" class="form-label">Durum</label>
                            <select class="form-select" id="status" required>
                                <option value="pending" <?php echo $expense['status'] === 'pending' ? 'selected' : ''; ?>>Ödeme Bekliyor</option>
                                <option value="paid" <?php echo $expense['status'] === 'paid' ? 'selected' : ''; ?>>Ödendi</option>
                                <option value="rejected" <?php echo $expense['status'] === 'rejected' ? 'selected' : ''; ?>>Reddedildi</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Butonlar -->
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" onclick="window.close()">
                            <i class="fas fa-times me-2"></i>İptal
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#editExpenseForm').on('submit', function(e) {
                e.preventDefault();
                
                const formData = {
                    expense_id: $('#expense_id').val(),
                    isim: $('#isim').val(),
                    soyisim: $('#soyisim').val(),
                    iban: $('#iban').val(),
                    total: $('#total').val(),
                    status: $('#status').val()
                };
                
                // AJAX ile güncelleme
                $.ajax({
                    url: 'update_expense.php',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            alert('Gider başarıyla güncellendi!');
                            window.opener.location.reload(); // Ana sayfayı yenile
                            window.close(); // Bu pencereyi kapat
                        } else {
                            alert('Hata: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Sunucu hatası oluştu!');
                    }
                });
            });
        });
    </script>
</body>
</html>


