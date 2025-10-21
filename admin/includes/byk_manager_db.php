<?php
require_once 'database.php';

class BYKManager {
    
    /**
     * BYK kategorilerini veritabanından getir
     */
    public static function getBYKCategories() {
        return DBHelper::getBYKCategories();
    }
    
    /**
     * BYK alt birimlerini veritabanından getir
     */
    public static function getBYKSubUnits($bykCategoryId = null) {
        return DBHelper::getBYKSubUnits($bykCategoryId);
    }
    
    /**
     * BYK istatistiklerini getir
     */
    public static function getBYKStats() {
        $db = Database::getInstance();
        
        $stats = [];
        $categories = self::getBYKCategories();
        
        foreach ($categories as $category) {
            // Kullanıcı sayısı
            $userCount = $db->fetchOne(
                "SELECT COUNT(*) as count FROM users WHERE byk_category_id = ? AND status = 'active'",
                [$category['id']]
            )['count'];
            
            // Etkinlik sayısı
            $eventCount = $db->fetchOne(
                "SELECT COUNT(*) as count FROM events WHERE byk_category_id = ? AND status = 'active'",
                [$category['id']]
            )['count'];
            
            // Proje sayısı
            $projectCount = $db->fetchOne(
                "SELECT COUNT(*) as count FROM projects WHERE byk_category_id = ? AND status IN ('planning', 'active')",
                [$category['id']]
            )['count'];
            
            $stats[$category['code']] = [
                'name' => $category['name'],
                'color' => $category['color'],
                'users' => $userCount,
                'events' => $eventCount,
                'projects' => $projectCount
            ];
        }
        
        return $stats;
    }
    
    /**
     * BYK kategorisi ekle
     */
    public static function addBYKCategory($code, $name, $color, $description = '') {
        $db = Database::getInstance();
        
        $data = [
            'code' => $code,
            'name' => $name,
            'color' => $color,
            'description' => $description
        ];
        
        return $db->insert('byk_categories', $data);
    }
    
    /**
     * BYK kategorisi güncelle
     */
    public static function updateBYKCategory($id, $data) {
        $db = Database::getInstance();
        
        return $db->update('byk_categories', $data, 'id = ?', [$id]);
    }
    
    /**
     * BYK kategorisi sil
     */
    public static function deleteBYKCategory($id) {
        $db = Database::getInstance();
        
        return $db->delete('byk_categories', 'id = ?', [$id]);
    }
    
    /**
     * BYK alt birimi ekle
     */
    public static function addBYKSubUnit($bykCategoryId, $name, $description = '') {
        $db = Database::getInstance();
        
        $data = [
            'byk_category_id' => $bykCategoryId,
            'name' => $name,
            'description' => $description
        ];
        
        return $db->insert('byk_sub_units', $data);
    }
    
    /**
     * BYK alt birimi güncelle
     */
    public static function updateBYKSubUnit($id, $data) {
        $db = Database::getInstance();
        
        return $db->update('byk_sub_units', $data, 'id = ?', [$id]);
    }
    
    /**
     * BYK alt birimi sil
     */
    public static function deleteBYKSubUnit($id) {
        $db = Database::getInstance();
        
        return $db->delete('byk_sub_units', 'id = ?', [$id]);
    }
    
    /**
     * BYK kodu ile kategori getir
     */
    public static function getBYKCategoryByCode($code) {
        $db = Database::getInstance();
        
        return $db->fetchOne(
            "SELECT * FROM byk_categories WHERE code = ?",
            [$code]
        );
    }
    
    /**
     * BYK ID ile kategori getir
     */
    public static function getBYKCategoryById($id) {
        $db = Database::getInstance();
        
        return $db->fetchOne(
            "SELECT * FROM byk_categories WHERE id = ?",
            [$id]
        );
    }
    
    /**
     * BYK alt birimi ID ile getir
     */
    public static function getBYKSubUnitById($id) {
        $db = Database::getInstance();
        
        return $db->fetchOne(
            "SELECT * FROM byk_sub_units WHERE id = ?",
            [$id]
        );
    }
    
    /**
     * BYK renk kodunu getir
     */
    public static function getBYKColor($code) {
        $category = self::getBYKCategoryByCode($code);
        return $category ? $category['color'] : '#6c757d';
    }
    
    /**
     * BYK adını getir
     */
    public static function getBYKName($code) {
        $category = self::getBYKCategoryByCode($code);
        return $category ? $category['name'] : 'Bilinmeyen';
    }
    
    /**
     * BYK dropdown'ı için HTML oluştur
     */
    public static function generateBYKDropdown($selectedBYK = '', $name = 'byk', $id = 'byk') {
        $html = '<select name="' . $name . '" id="' . $id . '" class="form-select">';
        $html .= '<option value="">BYK Seçiniz</option>';
        
        $categories = self::getBYKCategories();
        foreach ($categories as $category) {
            $selected = ($selectedBYK === $category['code']) ? 'selected' : '';
            $html .= '<option value="' . $category['code'] . '" ' . $selected . '>' . $category['code'] . ' - ' . $category['name'] . '</option>';
        }
        
        $html .= '</select>';
        return $html;
    }
    
    /**
     * Alt birim dropdown'ı için HTML oluştur
     */
    public static function generateSubUnitDropdown($selectedSubUnit = '', $name = 'sub_unit', $id = 'sub_unit', $bykCode = '') {
        $html = '<select name="' . $name . '" id="' . $id . '" class="form-select">';
        $html .= '<option value="">Alt Birim Seçiniz</option>';
        
        $subUnits = self::getBYKSubUnits();
        if ($bykCode) {
            $category = self::getBYKCategoryByCode($bykCode);
            if ($category) {
                $subUnits = self::getBYKSubUnits($category['id']);
            }
        }
        
        foreach ($subUnits as $unit) {
            $selected = ($selectedSubUnit === $unit['id']) ? 'selected' : '';
            $html .= '<option value="' . $unit['id'] . '" ' . $selected . '>' . $unit['name'] . '</option>';
        }
        
        $html .= '</select>';
        return $html;
    }
    
    /**
     * Tüm alt birimleri getir
     */
    public static function getAllSubUnits() {
        $db = Database::getInstance();
        
        return $db->fetchAll(
            "SELECT * FROM byk_sub_units ORDER BY name"
        );
    }
    
    /**
     * Alt birim adı ile getir
     */
    public static function getSubUnitByName($name) {
        $db = Database::getInstance();
        
        return $db->fetchOne(
            "SELECT * FROM byk_sub_units WHERE name = ?",
            [$name]
        );
    }
}
?>
