<?php
/**
 * Üye - İade Talebi Formu
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';

Middleware::requireUye();

$auth = new Auth();
$user = $auth->getUser();

$pageTitle = 'İade Talebi Formu';
$formUrl = '/Hesaplama/index.html';

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h1 class="h4 mb-2">
                                    <i class="fas fa-hand-holding-usd me-2 text-success"></i>İade Talebi Formu
                                </h1>
                                <p class="text-muted mb-0">
                                    Ücretini kendisi ödeyip geri iade almak isteyen üyeler için güvenli form.
                                </p>
                            </div>
                            <div class="text-end">
                                <a href="<?php echo $formUrl; ?>" target="_blank" rel="noopener" class="btn btn-success">
                                    <i class="fas fa-external-link-alt me-1"></i>Yeni Sekmede Aç
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-lg-4">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="text-uppercase text-muted fw-semibold small">Formu Gönderirken</h6>
                                    <ul class="small ps-3 mb-0">
                                        <li>Ödeme yaptığınız kalemleri tek tek ekleyin.</li>
                                        <li>IBAN bilgilerinizi doğru girip ekran sonunda PDF oluşturun.</li>
                                        <li>Belgelerin fotoğrafını veya PDF çıktısını eklemeyi unutmayın.</li>
                                        <li>Formu doldurduktan sonra “Gideri bildir” butonuna basın.</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-lg-8">
                                <div class="ratio ratio-16x9" style="min-height: 600px;">
                                    <iframe 
                                        src="<?php echo $formUrl; ?>" 
                                        title="İade Talebi Formu"
                                        style="border: 0; border-radius: 1rem;"
                                        allowfullscreen
                                    ></iframe>
                                </div>
                                <div class="text-muted small mt-2">
                                    Eğer form düzgün görünmüyorsa yukarıdaki "Yeni Sekmede Aç" butonunu kullanabilirsiniz.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>


