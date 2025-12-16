<?php
/**
 * Auto-Save Debugger
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';

// Disable default error handling to see raw errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$gundemId = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

echo "<!DOCTYPE html><html><head><title>Auto-Save Debugger</title><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'></head><body class='p-4'>";
echo "<div class='container'>";
echo "<h1>Auto-Save Debugger</h1>";
echo "<p><a href='/admin/dashboard.php' class='btn btn-secondary btn-sm'>Back to Dashboard</a></p>";

// 1. Check Database Schema
echo "<div class='card mb-4'><div class='card-header fw-bold'>1. Database Schema Check</div><div class='card-body'>";
try {
    $columns = $db->fetchAll("DESCRIBE toplanti_gundem");
    $hasColumn = false;
    echo "<table class='table table-sm table-bordered'><thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead><tbody>";
    foreach ($columns as $col) {
        $bgInfo = ($col['Field'] === 'gorusme_notlari') ? 'table-success' : '';
        echo "<tr class='$bgInfo'>";
        foreach ($col as $key => $val) {
            echo "<td>" . htmlspecialchars($val ?? 'NULL') . "</td>";
        }
        echo "</tr>";
        if ($col['Field'] === 'gorusme_notlari') $hasColumn = true;
    }
    echo "</tbody></table>";
    
    if ($hasColumn) {
        echo "<div class='alert alert-success'>✅ 'gorusme_notlari' column exists and is detected by PHP.</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ 'gorusme_notlari' column is MISSING! please run migration again.</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error checking schema: " . $e->getMessage() . "</div>";
}
echo "</div></div>";

// 2. Select Agenda Item to Test
echo "<div class='card mb-4'><div class='card-header fw-bold'>2. Select Agenda Item to Test</div><div class='card-body'>";
try {
    $lastItems = $db->fetchAll("SELECT * FROM toplanti_gundem ORDER BY gundem_id DESC LIMIT 10");
    if (empty($lastItems)) {
        echo "<p>No agenda items found.</p>";
    } else {
        echo "<p>Latest 10 items:</p><ul class='list-group'>";
        foreach ($lastItems as $item) {
            $active = ($gundemId == $item['gundem_id']) ? 'active' : '';
            echo "<a href='?id=" . $item['gundem_id'] . "' class='list-group-item list-group-item-action $active'>";
            echo "ID: <strong>" . $item['gundem_id'] . "</strong> - " . htmlspecialchars($item['baslik']) . " (Current Note Length: " . strlen($item['gorusme_notlari'] ?? '') . ")";
            echo "</a>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>" . $e->getMessage() . "</div>";
}
echo "</div></div>";

// 3. Test API Call
if ($gundemId) {
    echo "<div class='card mb-4'><div class='card-header fw-bold'>3. Test API Call (Simulated)</div><div class='card-body'>";
    
    echo "<form method='post' action='?id=$gundemId&action=test_api' class='mb-3'>";
    echo "<div class='input-group'>";
    echo "<input type='text' name='test_note' class='form-control' value='Test Note " . date('H:i:s') . "' placeholder='Enter parsed text'>";
    echo "<button type='submit' class='btn btn-primary'>Simulate API Request</button>";
    echo "</div>";
    echo "</form>";

    if ($action === 'test_api') {
        $testNote = $_POST['test_note'] ?? 'Test Note';
        
        echo "<h5>Testing API Logic Directly:</h5>";
        echo "<div class='bg-light p-3 border rounded mb-3'>";
        
        try {
            // Direct Logic Test
            $stmt = $db->query("
                UPDATE toplanti_gundem 
                SET gorusme_notlari = ? 
                WHERE gundem_id = ?
            ", [$testNote, $gundemId]);
            
            $rowCount = $stmt->rowCount();
            
            echo "<strong>Query Executed:</strong> UPDATE toplanti_gundem SET gorusme_notlari = '$testNote' WHERE gundem_id = $gundemId<br>";
            echo "<strong>Rows Affected:</strong> " . $rowCount . "<br>";
            
            if ($rowCount > 0 || $testNote === '') {
                 echo "<span class='text-success'>✓ Database update appears successful.</span>";
            } else {
                 echo "<span class='text-warning'>! Database reported 0 rows updated (Value might be same as before or ID not found).</span>";
            }
            
            // Check Current Value
            $currentParam = $db->fetch("SELECT gorusme_notlari FROM toplanti_gundem WHERE gundem_id = ?", [$gundemId]);
            echo "<br><strong>Verified Value in DB:</strong> " . htmlspecialchars($currentParam['gorusme_notlari']);
            
        } catch (Exception $e) {
            echo "<div class='text-danger'>Exception: " . $e->getMessage() . "</div>";
        }
        echo "</div>";
        
        echo "<h5>Testing Actual API Endpoint (via cURL)</h5>";
        $apiUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/api/update-agenda-note.php";
        echo "<p>Target: $apiUrl</p>";
        
        $data = json_encode(['gundem_id' => $gundemId, 'notlar' => $testNote]);
        
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data),
            'Cookie: ' . $_SERVER['HTTP_COOKIE'] // Pass current session
        ));
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        echo "<div class='bg-dark text-white p-3 rounded font-monospace'>";
        echo "HTTP Code: $httpCode<br>";
        if ($curlError) echo "cURL Error: $curlError<br>";
        echo "Response Body:<br>";
        echo htmlspecialchars($result);
        echo "</div>";
    }
    
    echo "</div></div>";
}

echo "</div></body></html>";
