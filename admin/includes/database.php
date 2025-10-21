<?php
/**
 * AIF Otomasyon Veritabanı Bağlantı Dosyası
 * MySQL veritabanı bağlantısı ve temel işlemler
 */

class Database {
    private static $instance = null;
    private $connection;
    
    // Veritabanı ayarları - Hosting ortamı için
    private $host = 'localhost'; // Hosting'de genellikle localhost
    private $database = 'd0451622';
    private $username = 'd0451622';
    private $password = '01528797Mb##';
    private $charset = 'utf8mb4';
    
    private function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
        ];
        
        try {
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Güvenli SQL sorgusu çalıştır
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            error_log("SQL: " . $sql);
            error_log("Params: " . json_encode($params));
            throw new Exception("Veritabanı hatası: " . $e->getMessage());
        }
    }
    
    /**
     * Tek satır veri getir
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Tüm satırları getir
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * INSERT işlemi
     */
    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        
        return $this->connection->lastInsertId();
    }
    
    /**
     * UPDATE işlemi
     */
    public function update($table, $data, $where, $whereParams = []) {
        // Use positional placeholders to avoid mixing named and positional params
        $setParts = [];
        $values = [];
        foreach ($data as $key => $value) {
            $setParts[] = "{$key} = ?";
            $values[] = $value;
        }
        $setClause = implode(', ', $setParts);

        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge($values, array_values($whereParams));

        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * DELETE işlemi
     */
    public function delete($table, $where, $params = []) {
        try {
            $sql = "DELETE FROM {$table} WHERE {$where}";
            error_log("Delete SQL: " . $sql);
            error_log("Delete Params: " . json_encode($params));
            
            $stmt = $this->query($sql, $params);
            $rowCount = $stmt->rowCount();
            error_log("Delete Row Count: " . $rowCount);
            
            return $rowCount;
        } catch (Exception $e) {
            error_log("Database Delete Error: " . $e->getMessage());
            error_log("Database Delete Error Trace: " . $e->getTraceAsString());
            throw new Exception("Silme işlemi başarısız: " . $e->getMessage());
        }
    }
    
    /**
     * İşlem başlat
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * İşlemi onayla
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * İşlemi geri al
     */
    public function rollback() {
        return $this->connection->rollback();
    }
    
    /**
     * Son eklenen ID'yi getir
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Veritabanı bağlantısını kapat
     */
    public function close() {
        $this->connection = null;
    }
}

/**
 * Veritabanı yardımcı fonksiyonları
 */
class DBHelper {
    
    /**
     * BYK kategorilerini getir
     */
    public static function getBYKCategories() {
        $db = Database::getInstance();
        return $db->fetchAll("SELECT * FROM byk_categories ORDER BY code");
    }
    
    /**
     * BYK alt birimlerini getir
     */
    public static function getBYKSubUnits($bykCategoryId = null) {
        $db = Database::getInstance();
        
        if ($bykCategoryId) {
            return $db->fetchAll(
                "SELECT * FROM byk_sub_units WHERE byk_category_id = ? ORDER BY name",
                [$bykCategoryId]
            );
        }
        
        return $db->fetchAll("SELECT * FROM byk_sub_units ORDER BY byk_category_id, name");
    }
    
    /**
     * Kullanıcıları getir
     */
    public static function getUsers($filters = []) {
        $db = Database::getInstance();
        
        try {
            // Önce JOIN ile dene
            $sql = "SELECT u.*, bc.name as byk_name, bc.code as byk_code, bc.color as byk_color,
                           bsu.name as sub_unit_name
                    FROM users u
                    LEFT JOIN byk_categories bc ON u.byk_category_id = bc.id
                    LEFT JOIN byk_sub_units bsu ON u.sub_unit_id = bsu.id
                    WHERE 1=1";
            
            $params = [];
            
            if (!empty($filters['role'])) {
                $sql .= " AND u.role = ?";
                $params[] = $filters['role'];
            }
            
            if (!empty($filters['byk_category_id'])) {
                $sql .= " AND u.byk_category_id = ?";
                $params[] = $filters['byk_category_id'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND u.status = ?";
                $params[] = $filters['status'];
            }
            
            $sql .= " ORDER BY u.first_name, u.last_name";
            
            return $db->fetchAll($sql, $params);
            
        } catch (Exception $e) {
            // JOIN başarısız olursa sadece users tablosundan çek
            error_log("getUsers JOIN failed: " . $e->getMessage());
            
            $sql = "SELECT * FROM users WHERE 1=1";
            $params = [];
            
            if (!empty($filters['role'])) {
                $sql .= " AND role = ?";
                $params[] = $filters['role'];
            }
            
            if (!empty($filters['byk_category_id'])) {
                $sql .= " AND byk_category_id = ?";
                $params[] = $filters['byk_category_id'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND status = ?";
                $params[] = $filters['status'];
            }
            
            $sql .= " ORDER BY first_name, last_name";
            
            return $db->fetchAll($sql, $params);
        }
    }
    
    /**
     * Kullanıcı yetkilerini getir
     */
    public static function getUserPermissions($userId) {
        $db = Database::getInstance();
        
        return $db->fetchAll(
            "SELECT m.name, m.display_name, m.icon, up.can_read, up.can_write, up.can_admin
             FROM user_permissions up
             JOIN modules m ON up.module_id = m.id
             WHERE up.user_id = ?",
            [$userId]
        );
    }
    
    /**
     * Etkinlikleri getir
     */
    public static function getEvents($filters = []) {
        $db = Database::getInstance();
        
        $sql = "SELECT e.*, bc.name as byk_name, bc.code as byk_code, bc.color as byk_color,
                       u.full_name as created_by_name
                FROM events e
                LEFT JOIN byk_categories bc ON e.byk_category_id = bc.id
                LEFT JOIN users u ON e.created_by = u.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['byk_category_id'])) {
            $sql .= " AND e.byk_category_id = ?";
            $params[] = $filters['byk_category_id'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND e.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['start_date'])) {
            $sql .= " AND e.start_date >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND e.start_date <= ?";
            $params[] = $filters['end_date'];
        }
        
        $sql .= " ORDER BY e.start_date";
        
        return $db->fetchAll($sql, $params);
    }
    
    /**
     * Sistem ayarlarını getir
     */
    public static function getSystemSettings() {
        $db = Database::getInstance();
        $settings = $db->fetchAll("SELECT setting_key, setting_value, setting_type FROM system_settings");
        
        $result = [];
        foreach ($settings as $setting) {
            $value = $setting['setting_value'];
            
            // Tip dönüşümü
            switch ($setting['setting_type']) {
                case 'number':
                    $value = (int)$value;
                    break;
                case 'boolean':
                    $value = $value === 'true';
                    break;
                case 'json':
                    $value = json_decode($value, true);
                    break;
            }
            
            $result[$setting['setting_key']] = $value;
        }
        
        return $result;
    }
    
    /**
     * Sistem ayarı güncelle
     */
    public static function updateSystemSetting($key, $value, $userId = null) {
        $db = Database::getInstance();
        
        $data = [
            'setting_value' => $value,
            'updated_by' => $userId
        ];
        
        return $db->update('system_settings', $data, 'setting_key = ?', [$key]);
    }
}

// Veritabanı bağlantısını test et
try {
    $db = Database::getInstance();
    $db->query("SELECT 1"); // Basit test sorgusu
} catch (Exception $e) {
    // Veritabanı bağlantı hatası
    error_log("Database connection failed: " . $e->getMessage());
}
?>