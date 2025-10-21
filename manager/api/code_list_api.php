<?php
require_once '../config.php';

// Login kontrolü - Geçici olarak devre dışı bırakıldı
// SessionManager::requireRole(['superadmin', 'manager']);

header('Content-Type: application/json');

// Veritabanı bağlantısı
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı bağlantı hatası: ' . $e->getMessage()
    ]);
    exit;
}

// Database sınıfı
class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public static function getInstance($pdo = null) {
        if (self::$instance === null && $pdo) {
            self::$instance = new self($pdo);
        }
        return self::$instance;
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = ':' . implode(', :', $fields);
        $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES ({$placeholders})";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $this->pdo->lastInsertId();
    }
    
    public function update($table, $data, $where) {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = "{$key} = :{$key}";
        }
        
        $whereClause = [];
        foreach ($where as $key => $value) {
            $whereClause[] = "{$key} = :where_{$key}";
        }
        
        $sql = "UPDATE {$table} SET " . implode(', ', $fields) . " WHERE " . implode(' AND ', $whereClause);
        
        $params = array_merge($data, array_combine(
            array_map(function($key) { return "where_{$key}"; }, array_keys($where)),
            array_values($where)
        ));
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function delete($table, $where) {
        $whereClause = [];
        foreach ($where as $key => $value) {
            $whereClause[] = "{$key} = :{$key}";
        }
        
        $sql = "DELETE FROM {$table} WHERE " . implode(' AND ', $whereClause);
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($where);
    }
}

$db = Database::getInstance($pdo);

// Code List kategorileri
$codeCategories = [
    'byk' => [
        'name' => 'BYK Kategorileri',
        'table' => 'byk_categories',
        'fields' => ['code', 'name', 'description'],
        'display_fields' => ['code', 'name']
    ],
    'positions' => [
        'name' => 'Görevler',
        'table' => 'positions',
        'fields' => ['name', 'description', 'level'],
        'display_fields' => ['name']
    ],
    'sub_units' => [
        'name' => 'Alt Birimler',
        'table' => 'sub_units',
        'fields' => ['name', 'description', 'byk_category_id'],
        'display_fields' => ['name']
    ],
    'expense_types' => [
        'name' => 'Gider Türleri',
        'table' => 'expense_types',
        'fields' => ['name', 'description', 'category'],
        'display_fields' => ['name']
    ]
];

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = Database::getInstance();
    
    switch ($action) {
        case 'get_items':
            $category = $_GET['category'] ?? 'byk';
            $currentCategory = $codeCategories[$category] ?? $codeCategories['byk'];
            
            $items = $db->fetchAll("SELECT * FROM {$currentCategory['table']} ORDER BY id DESC");
            
            echo json_encode([
                'success' => true,
                'data' => $items,
                'category' => $currentCategory
            ]);
            break;
            
        case 'add_item':
            $category = $_POST['category'] ?? 'byk';
            $currentCategory = $codeCategories[$category] ?? $codeCategories['byk'];
            
            $data = [];
            foreach ($currentCategory['fields'] as $field) {
                if (isset($_POST[$field])) {
                    $data[$field] = $_POST[$field];
                }
            }
            
            if (empty($data)) {
                throw new Exception('Veri bulunamadı');
            }
            
            $id = $db->insert($currentCategory['table'], $data);
            
            echo json_encode([
                'success' => true,
                'message' => 'Öğe başarıyla eklendi',
                'id' => $id
            ]);
            break;
            
        case 'update_item':
            $category = $_POST['category'] ?? 'byk';
            $currentCategory = $codeCategories[$category] ?? $codeCategories['byk'];
            $id = $_POST['id'] ?? 0;
            
            if (!$id) {
                throw new Exception('ID bulunamadı');
            }
            
            $data = [];
            foreach ($currentCategory['fields'] as $field) {
                if (isset($_POST[$field])) {
                    $data[$field] = $_POST[$field];
                }
            }
            
            if (empty($data)) {
                throw new Exception('Veri bulunamadı');
            }
            
            $db->update($currentCategory['table'], $data, ['id' => $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Öğe başarıyla güncellendi'
            ]);
            break;
            
        case 'delete_item':
            $category = $_GET['category'] ?? 'byk';
            $currentCategory = $codeCategories[$category] ?? $codeCategories['byk'];
            $id = $_GET['id'] ?? 0;
            
            if (!$id) {
                throw new Exception('ID bulunamadı');
            }
            
            $db->delete($currentCategory['table'], ['id' => $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Öğe başarıyla silindi'
            ]);
            break;
            
        case 'get_item':
            $category = $_GET['category'] ?? 'byk';
            $currentCategory = $codeCategories[$category] ?? $codeCategories['byk'];
            $id = $_GET['id'] ?? 0;
            
            if (!$id) {
                throw new Exception('ID bulunamadı');
            }
            
            $item = $db->fetch("SELECT * FROM {$currentCategory['table']} WHERE id = ?", [$id]);
            
            if (!$item) {
                throw new Exception('Öğe bulunamadı');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $item
            ]);
            break;
            
        case 'get_categories':
            echo json_encode([
                'success' => true,
                'data' => $codeCategories
            ]);
            break;
            
        default:
            throw new Exception('Geçersiz işlem');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
