<?php
/**
 * Üye - Duyurular Listesi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireUye();
Middleware::requireModulePermission('uye_duyurular');

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Duyurular';
$bykId = $user['byk_id'];

$duyurular = [];
$toplamDuyuru = 0;

if ($bykId) {
    $duyurular = $db->fetchAll("
        SELECT d.*, CONCAT(k.ad, ' ', k.soyad) as olusturan_adi
        FROM duyurular d
        LEFT JOIN kullanicilar k ON d.olusturan_id = k.kullanici_id
        WHERE d.byk_id = ? AND d.aktif = 1
        ORDER BY d.olusturma_tarihi DESC
        LIMIT 50
    ", [$bykId]);
    $toplamDuyuru = count($duyurular);
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Modern Design Assets -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --primary: #009872;
        --primary-light: rgba(0, 152, 114, 0.1);
        --text-dark: #1e293b;
        --text-muted: #64748b;
        --card-bg: rgba(255, 255, 255, 0.9);
        --glass-border: 1px solid rgba(255, 255, 255, 0.5);
    }

    body {
        font-family: 'Inter', sans-serif;
        background: radial-gradient(circle at 0% 0%, rgba(0, 152, 114, 0.08) 0%, transparent 50%),
                    radial-gradient(circle at 100% 100%, rgba(0, 152, 114, 0.05) 0%, transparent 50%),
                    #f8fafc;
        color: var(--text-dark);
    }

    /* Glass Cards */
    .card {
        background: var(--card-bg);
        backdrop-filter: blur(10px);
        border: var(--glass-border);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border-radius: 1rem;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
    }

    .card-header {
        background: transparent;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        padding: 1.25rem 1.5rem;
        font-size: 1rem;
        color: var(--text-dark);
    }

    /* CSS Overrides for Mobile Layout Fix */
    .dashboard-layout {
        display: block;
    }

    .sidebar-wrapper {
        display: none;
    }

    .content-wrapper {
        width: 100% !important;
        min-width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        padding: 1rem !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    .main-content {
        width: 100%;
    }

    /* Desktop View */
    @media (min-width: 992px) {
        .dashboard-layout {
            display: flex;
            flex-direction: row;
        }

        .sidebar-wrapper {
            display: block;
            width: 250px;
            flex-shrink: 0;
            z-index: 1000;
        }
        
        .main-content {
            flex-grow: 1;
            width: auto;
        }

        .content-wrapper {
            padding: 1.5rem 2rem !important;
            max-width: 1400px !important;
            margin: 0 auto !important;
        }
    }
</style>

<div class="dashboard-layout">
    <!-- Sidebar Wrapper -->
    <div class="sidebar-wrapper">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="fas fa-bullhorn me-2"></i>Duyurular
                </h1>
                <small class="text-muted">BYK duyuruları</small>
            </div>
            <span class="badge bg-primary fs-6">Aktif: <?php echo $toplamDuyuru; ?></span>
        </div>
        
        <div class="row">
            <?php if (!$bykId): ?>
                <div class="col-12">
                    <div class="alert alert-warning">
                        BYK bilgisi bulunamadığı için duyurular listelenemiyor. Lütfen sistem yöneticinizle iletişime geçin.
                    </div>
                </div>
            <?php elseif (empty($duyurular)): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center text-muted py-5">
                            <i class="fas fa-bullhorn fa-2x mb-3"></i>
                            <p class="mb-0">Bu BYK için aktif duyuru bulunmamaktadır.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($duyurular as $duyuru): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <strong><?php echo htmlspecialchars($duyuru['baslik']); ?></strong>
                                <span class="badge bg-success"><?php echo date('d.m.Y', strtotime($duyuru['olusturma_tarihi'])); ?></span>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-3">
                                    <?php echo nl2br(htmlspecialchars($duyuru['icerik'])); ?>
                                </p>
                                <div class="d-flex justify-content-between text-muted small">
                                    <span><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($duyuru['olusturan_adi'] ?? 'Sistem'); ?></span>
                                    <span><i class="fas fa-clock me-1"></i><?php echo date('d.m.Y H:i', strtotime($duyuru['olusturma_tarihi'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    </div>
    </main>
</div>

<?php
include __DIR__ . '/../includes/footer.php';
?>


