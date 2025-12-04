<?php
/**
 * Ana Yönetici - Kontrol Paneli
 * Tüm sistemin genel görünümü
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

// Yetki kontrolü
Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Kontrol Paneli';
$enableCharts = true;

// İstatistikleri cache'le
$stats = Cache::remember('dashboard_stats', 60, function () use ($db) {
    try {
        $toplamByk = $db->fetch("SELECT COUNT(*) as count FROM byk_categories")['count'];
    } catch (Exception $e) {
        $toplamByk = $db->fetch("SELECT COUNT(*) as count FROM byk WHERE aktif = 1")['count'];
    }

    return [
        'toplam_kullanici' => $db->fetch("SELECT COUNT(*) as count FROM kullanicilar WHERE aktif = 1")['count'],
        'toplam_byk' => $toplamByk,
        'toplam_etkinlik' => $db->fetch("SELECT COUNT(*) as count FROM etkinlikler WHERE baslangic_tarihi >= CURDATE()")['count'],
        'toplam_toplanti' => $db->fetch("SELECT COUNT(*) as count FROM toplantilar WHERE durum = 'planlandi'")['count'],
        'bekleyen_izin' => $db->fetch("SELECT COUNT(*) as count FROM izin_talepleri WHERE durum = 'beklemede'")['count'],
        'bekleyen_harcama' => $db->fetch("SELECT COUNT(*) as count FROM harcama_talepleri WHERE durum = 'beklemede'")['count'],
    ];
});

// Son aktiviteler
$son_aktiviteler = Cache::remember('dashboard_son_aktiviteler', 120, function () use ($db) {
    return $db->fetchAll("
        SELECT 
            'toplanti' as tip,
            t.baslik as baslik,
            t.olusturma_tarihi as tarih,
            CONCAT(u.ad, ' ', u.soyad) as kullanici
        FROM toplantilar t
        INNER JOIN kullanicilar u ON t.olusturan_id = u.kullanici_id
        ORDER BY t.olusturma_tarihi DESC
        LIMIT 10
    ");
});

// BYK bazlı kullanıcı dağılımı (Chart.js için)
try {
    $byk_kullanicilar = Cache::remember('dashboard_byk_kullanicilar', 300, function () use ($db) {
        return $db->fetchAll("
            SELECT bc.name as byk_adi, 
                   COUNT(DISTINCT k.kullanici_id) as kullanici_sayisi
            FROM byk_categories bc
            LEFT JOIN users u ON u.byk_category_id = bc.id AND u.status = 'active'
            LEFT JOIN kullanicilar k ON k.byk_id = (SELECT byk_id FROM byk WHERE byk_kodu = bc.code) AND k.aktif = 1
            GROUP BY bc.id, bc.name
            ORDER BY kullanici_sayisi DESC
            LIMIT 10
        ");
    });
} catch (Exception $e) {
    // byk_categories yoksa eski byk tablosunu kullan
    $byk_kullanicilar = Cache::remember('dashboard_byk_kullanicilar_fallback', 300, function () use ($db) {
        return $db->fetchAll("
            SELECT b.byk_adi, COUNT(k.kullanici_id) as kullanici_sayisi
            FROM byk b
            LEFT JOIN kullanicilar k ON b.byk_id = k.byk_id AND k.aktif = 1
            WHERE b.aktif = 1
            GROUP BY b.byk_id, b.byk_adi
            ORDER BY kullanici_sayisi DESC
            LIMIT 10
        ");
    });
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-tachometer-alt me-2"></i>Kontrol Paneli
                </h1>
                <div>
                    <small class="text-muted">Son güncelleme: <?php echo date('d.m.Y H:i'); ?></small>
                </div>
            </div>
            
            <!-- İstatistik Kartları -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Toplam Kullanıcı</div>
                                <div class="stat-value"><?php echo $stats['toplam_kullanici']; ?></div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card stat-card success">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Aktif BYK</div>
                                <div class="stat-value"><?php echo $stats['toplam_byk']; ?></div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-building"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card stat-card info">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Yaklaşan Etkinlikler</div>
                                <div class="stat-value"><?php echo $stats['toplam_etkinlik']; ?></div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-calendar"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card stat-card warning">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Planlanan Toplantılar</div>
                                <div class="stat-value"><?php echo $stats['toplam_toplanti']; ?></div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-users-cog"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bekleyen İşlemler -->
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>Bekleyen İzin Talepleri
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="mb-0"><?php echo $stats['bekleyen_izin']; ?></h3>
                                <a href="/admin/izin-talepleri.php?durum=beklemede" class="btn btn-sm btn-primary">
                                    İncele <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-money-bill-wave text-danger me-2"></i>Bekleyen Harcama Talepleri
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="mb-0"><?php echo $stats['bekleyen_harcama']; ?></h3>
                                <a href="/admin/harcama-talepleri.php?durum=beklemede" class="btn btn-sm btn-primary">
                                    İncele <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Grafikler -->
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-pie me-2"></i>BYK Bazlı Kullanıcı Dağılımı
                        </div>
                        <div class="card-body">
                            <canvas id="bykChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-bar me-2"></i>Son Aktivite Grafiği
                        </div>
                        <div class="card-body">
                            <canvas id="activityChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Son Aktiviteler -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-clock me-2"></i>Son Aktiviteler
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tip</th>
                                            <th>Başlık</th>
                                            <th>Kullanıcı</th>
                                            <th>Tarih</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($son_aktiviteler)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">Henüz aktivite bulunmamaktadır.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($son_aktiviteler as $aktivite): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-info">
                                                            <?php echo ucfirst($aktivite['tip']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($aktivite['baslik']); ?></td>
                                                    <td><?php echo htmlspecialchars($aktivite['kullanici']); ?></td>
                                                    <td><?php echo date('d.m.Y H:i', strtotime($aktivite['tarih'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>
</main>

<script>
// BYK Kullanıcı Dağılımı Grafiği
const bykData = <?php echo json_encode($byk_kullanicilar); ?>;
const ctx1 = document.getElementById('bykChart').getContext('2d');
new Chart(ctx1, {
    type: 'pie',
    data: {
        labels: bykData.map(item => item.byk_adi),
        datasets: [{
            data: bykData.map(item => parseInt(item.kullanici_sayisi)),
            backgroundColor: [
                '#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8',
                '#6c757d', '#6610f2', '#e83e8c', '#fd7e14', '#20c997'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Aktivite Grafiği (Örnek - gerçek veri için API gerekli)
const ctx2 = document.getElementById('activityChart').getContext('2d');
new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'],
        datasets: [{
            label: 'Aktivite Sayısı',
            data: [12, 19, 15, 25, 22, 18, 10],
            backgroundColor: '#007bff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

<?php
include __DIR__ . '/../includes/footer.php';
?>
