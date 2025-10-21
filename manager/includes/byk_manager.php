<?php
/**
 * BYK (Birim Yönetim Kurulu) Yapısı ve Alt Birimleri
 * AT, KT, KGT, GT şeklinde 4 farklı BYK ve alt birimleri
 */

class BYKManager {
    
    // BYK Ana Kategorileri
    const BYK_CATEGORIES = [
        'AT' => 'Ana Teşkilat',
        'KT' => 'Kadınlar Teşkilatı',
        'KGT' => 'Kadınlar Gençlik Teşkilatı',
        'GT' => 'Gençlik Teşkilatı'
    ];
    
    // BYK Renk Kodları
    const BYK_COLORS = [
        'AT' => [
            'primary' => '#dc3545',    // Kırmızı
            'light' => '#f8d7da',     // Açık kırmızı
            'dark' => '#b02a37'        // Koyu kırmızı
        ],
        'KT' => [
            'primary' => '#6f42c1',    // Mor
            'light' => '#e2d9f3',     // Açık mor
            'dark' => '#5a32a3'       // Koyu mor
        ],
        'KGT' => [
            'primary' => '#198754',    // Koyu yeşil
            'light' => '#d1e7dd',     // Açık yeşil
            'dark' => '#146c43'        // Daha koyu yeşil
        ],
        'GT' => [
            'primary' => '#0d6efd',    // Mavi
            'light' => '#cfe2ff',     // Açık mavi
            'dark' => '#0b5ed7'       // Koyu mavi
        ]
    ];
    
    // Alt Birimler - Ekran görüntüsündeki listeye göre
    const SUB_UNITS = [
        'Başkan' => 'Başkan',
        'BYK Üyesi' => 'BYK Üyesi',
        'Eğitim' => 'Eğitim',
        'Fuar' => 'Fuar',
        'Spor/Gezi (GOB)' => 'Spor/Gezi (GOB)',
        'Hac/Umre' => 'Hac/Umre',
        'İdari İşler' => 'İdari İşler',
        'İrşad' => 'İrşad',
        'Kurumsal İletişim' => 'Kurumsal İletişim',
        'Muhasebe' => 'Muhasebe',
        'Orta Öğretim' => 'Orta Öğretim',
        'Raggal' => 'Raggal',
        'Sosyal Hizmetler' => 'Sosyal Hizmetler',
        'Tanıtma' => 'Tanıtma',
        'Teftiş' => 'Teftiş',
        'Teşkilatlanma' => 'Teşkilatlanma',
        'Üniversiteler' => 'Üniversiteler'
    ];
    
    // BYK ve Alt Birim Eşleştirmeleri
    const BYK_SUB_UNITS = [
        'AT' => [
            'Başkan',
            'BYK Üyesi',
            'Eğitim',
            'Fuar',
            'Spor/Gezi (GOB)',
            'Hac/Umre',
            'İdari İşler',
            'İrşad',
            'Kurumsal İletişim',
            'Muhasebe',
            'Orta Öğretim',
            'Raggal',
            'Sosyal Hizmetler',
            'Tanıtma',
            'Teftiş',
            'Teşkilatlanma',
            'Üniversiteler'
        ],
        'KT' => [
            'Başkan',
            'BYK Üyesi',
            'Eğitim',
            'Fuar',
            'Spor/Gezi (GOB)',
            'Hac/Umre',
            'İdari İşler',
            'İrşad',
            'Kurumsal İletişim',
            'Muhasebe',
            'Orta Öğretim',
            'Raggal',
            'Sosyal Hizmetler',
            'Tanıtma',
            'Teftiş',
            'Teşkilatlanma',
            'Üniversiteler'
        ],
        'KGT' => [
            'Başkan',
            'BYK Üyesi',
            'Eğitim',
            'Fuar',
            'Spor/Gezi (GOB)',
            'Hac/Umre',
            'İdari İşler',
            'İrşad',
            'Kurumsal İletişim',
            'Muhasebe',
            'Orta Öğretim',
            'Raggal',
            'Sosyal Hizmetler',
            'Tanıtma',
            'Teftiş',
            'Teşkilatlanma',
            'Üniversiteler'
        ],
        'GT' => [
            'Başkan',
            'BYK Üyesi',
            'Eğitim',
            'Fuar',
            'Spor/Gezi (GOB)',
            'Hac/Umre',
            'İdari İşler',
            'İrşad',
            'Kurumsal İletişim',
            'Muhasebe',
            'Orta Öğretim',
            'Raggal',
            'Sosyal Hizmetler',
            'Tanıtma',
            'Teftiş',
            'Teşkilatlanma',
            'Üniversiteler'
        ]
    ];
    
    /**
     * Tüm BYK kategorilerini getir
     */
    public static function getBYKCategories() {
        return self::BYK_CATEGORIES;
    }
    
    /**
     * Tüm alt birimleri getir
     */
    public static function getSubUnits() {
        return self::SUB_UNITS;
    }
    
    /**
     * Belirli bir BYK'nın alt birimlerini getir
     */
    public static function getSubUnitsByBYK($bykCode) {
        return isset(self::BYK_SUB_UNITS[$bykCode]) ? self::BYK_SUB_UNITS[$bykCode] : [];
    }
    
    /**
     * BYK kodundan tam adını getir
     */
    public static function getBYKName($bykCode) {
        return isset(self::BYK_CATEGORIES[$bykCode]) ? self::BYK_CATEGORIES[$bykCode] : $bykCode;
    }
    
    /**
     * BYK renk kodlarını getir
     */
    public static function getBYKColors($bykCode) {
        return isset(self::BYK_COLORS[$bykCode]) ? self::BYK_COLORS[$bykCode] : self::BYK_COLORS['AT'];
    }
    
    /**
     * BYK ana rengini getir
     */
    public static function getBYKPrimaryColor($bykCode) {
        $colors = self::getBYKColors($bykCode);
        return $colors['primary'];
    }
    
    /**
     * BYK açık rengini getir
     */
    public static function getBYKLightColor($bykCode) {
        $colors = self::getBYKColors($bykCode);
        return $colors['light'];
    }
    
    /**
     * BYK koyu rengini getir
     */
    public static function getBYKDarkColor($bykCode) {
        $colors = self::getBYKColors($bykCode);
        return $colors['dark'];
    }
    
    /**
     * BYK ve alt birim dropdown'ları için HTML oluştur
     */
    public static function generateBYKDropdown($selectedBYK = '', $name = 'byk', $id = 'byk') {
        $html = '<select name="' . $name . '" id="' . $id . '" class="form-select">';
        $html .= '<option value="">BYK Seçiniz</option>';
        
        foreach (self::BYK_CATEGORIES as $code => $name) {
            $selected = ($selectedBYK === $code) ? 'selected' : '';
            $html .= '<option value="' . $code . '" ' . $selected . '>' . $code . ' - ' . $name . '</option>';
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
        
        $subUnits = $bykCode ? self::getSubUnitsByBYK($bykCode) : self::SUB_UNITS;
        
        foreach ($subUnits as $unit) {
            $selected = ($selectedSubUnit === $unit) ? 'selected' : '';
            $html .= '<option value="' . $unit . '" ' . $selected . '>' . $unit . '</option>';
        }
        
        $html .= '</select>';
        return $html;
    }
    
    /**
     * BYK istatistikleri için veri hazırla
     */
    public static function getBYKStats() {
        $stats = [];
        
        foreach (self::BYK_CATEGORIES as $code => $name) {
            $stats[$code] = [
                'name' => $name,
                'code' => $code,
                'sub_units_count' => count(self::getSubUnitsByBYK($code)),
                'sub_units' => self::getSubUnitsByBYK($code)
            ];
        }
        
        return $stats;
    }
}

// Demo kullanıcı verileri için BYK bilgileri ekle
$demoUsers = [
    [
        'id' => 1,
        'username' => 'superadmin',
        'password' => password_hash('123456', PASSWORD_DEFAULT),
        'full_name' => 'Super Admin',
        'email' => 'superadmin@aif.com',
        'role' => 'superadmin',
        'byk' => 'AT',
        'sub_unit' => 'Başkan',
        'created_at' => '2024-01-01 00:00:00'
    ],
    [
        'id' => 2,
        'username' => 'admin_at',
        'password' => password_hash('123456', PASSWORD_DEFAULT),
        'full_name' => 'AT Başkanı',
        'email' => 'at@aif.com',
        'role' => 'manager',
        'byk' => 'AT',
        'sub_unit' => 'Başkan',
        'created_at' => '2024-01-01 00:00:00'
    ],
    [
        'id' => 3,
        'username' => 'admin_kt',
        'password' => password_hash('123456', PASSWORD_DEFAULT),
        'full_name' => 'KT Başkanı',
        'email' => 'kt@aif.com',
        'role' => 'manager',
        'byk' => 'KT',
        'sub_unit' => 'Başkan',
        'created_at' => '2024-01-01 00:00:00'
    ],
    [
        'id' => 4,
        'username' => 'member_egitim',
        'password' => password_hash('123456', PASSWORD_DEFAULT),
        'full_name' => 'Eğitim Sorumlusu',
        'email' => 'egitim@aif.com',
        'role' => 'member',
        'byk' => 'AT',
        'sub_unit' => 'Eğitim',
        'created_at' => '2024-01-01 00:00:00'
    ],
    [
        'id' => 5,
        'username' => 'member_fuar',
        'password' => password_hash('123456', PASSWORD_DEFAULT),
        'full_name' => 'Fuar Sorumlusu',
        'email' => 'fuar@aif.com',
        'role' => 'member',
        'byk' => 'KT',
        'sub_unit' => 'Fuar',
        'created_at' => '2024-01-01 00:00:00'
    ]
];
?>
