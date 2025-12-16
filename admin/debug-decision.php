<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

// Only admin
if ($user['role'] !== 'super_admin' && $user['role'] !== 'baskan') {
    die('Access denied');
}

$message = '';
$api_result = '';

// Handle Test POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/api/update-decision-text.php";
    
    $data = [
        'toplanti_id' => $_POST['toplanti_id'] ?? 5,
        'gundem_id' => $_POST['gundem_id'] ?? 1, 
        'karar_metni' => 'Test Decision ' . date('H:i:s')
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data)
        ]
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    $api_result = $result;
}

// Get Table Schema
$schema = $db->fetchAll("DESCRIBE toplanti_kararlar");

// Get Recent Decisions
$decisions = $db->fetchAll("SELECT * FROM toplanti_kararlar ORDER BY karar_id DESC LIMIT 5");

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Debug Decision API</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <h1>Debug: Decision Saving</h1>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">Test API Endpoint</div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label>Toplanti ID</label>
                            <input type="number" name="toplanti_id" value="5" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Gundem ID (Optional)</label>
                            <input type="number" name="gundem_id" value="1" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary">Send Test Request</button>
                    </form>
                    
                    <?php if ($api_result): ?>
                        <div class="mt-3">
                            <h6>API Response:</h6>
                            <pre class="bg-light p-2"><?php echo htmlspecialchars($api_result); ?></pre>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">Recent Decisions</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Toplanti</th>
                                    <th>Gundem</th>
                                    <th>Metin</th>
                                    <th>Oluşturma</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($decisions as $d): ?>
                                    <tr>
                                        <td><?php echo $d['karar_id']; ?></td>
                                        <td><?php echo $d['toplanti_id']; ?></td>
                                        <td><?php echo $d['gundem_id']; ?></td>
                                        <td><?php echo htmlspecialchars(substr($d['karar_metni'], 0, 50)); ?>...</td>
                                        <td><?php echo $d['olusturma_tarihi']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Table Schema: `toplanti_kararlar`</div>
                <div class="card-body">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Type</th>
                                <th>Null</th>
                                <th>Default</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schema as $col): ?>
                                <tr>
                                    <td><?php echo $col['Field']; ?></td>
                                    <td><?php echo $col['Type']; ?></td>
                                    <td><?php echo $col['Null']; ?></td>
                                    <td><?php echo $col['Default']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
