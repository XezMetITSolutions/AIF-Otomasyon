<?php
/**
 * Kullanıcı Yetki Yönetimi Sistemi
 * Admin hangi modülleri görebileceğini ve hangi yetkilere sahip olacağını belirler
 */

class PermissionManager {
    
    // Modül tanımları
    const MODULES = [
        'dashboard' => [
            'name' => 'Dashboard',
            'icon' => 'fas fa-tachometer-alt',
            'description' => 'Ana kontrol paneli'
        ],
        'users' => [
            'name' => 'Kullanıcılar',
            'icon' => 'fas fa-users',
            'description' => 'Kullanıcı yönetimi'
        ],
        'permissions' => [
            'name' => 'Yetki Yönetimi',
            'icon' => 'fas fa-shield-alt',
            'description' => 'Yetki ve izin yönetimi'
        ],
        'announcements' => [
            'name' => 'Duyurular',
            'icon' => 'fas fa-bullhorn',
            'description' => 'Duyuru yönetimi'
        ],
        'events' => [
            'name' => 'Etkinlikler',
            'icon' => 'fas fa-calendar-alt',
            'description' => 'Etkinlik yönetimi'
        ],
        'calendar' => [
            'name' => 'Takvim',
            'icon' => 'fas fa-calendar',
            'description' => 'Takvim görüntüleme'
        ],
        'inventory' => [
            'name' => 'Demirbaş Listesi',
            'icon' => 'fas fa-boxes',
            'description' => 'Demirbaş yönetimi'
        ],
        'meeting_reports' => [
            'name' => 'Toplantı Raporları',
            'icon' => 'fas fa-file-alt',
            'description' => 'Toplantı raporları'
        ],
        'reservations' => [
            'name' => 'Rezervasyon',
            'icon' => 'fas fa-bookmark',
            'description' => 'Rezervasyon yönetimi'
        ],
        'expenses' => [
            'name' => 'Para İadesi',
            'icon' => 'fas fa-undo',
            'description' => 'İade talepleri'
        ],
        'projects' => [
            'name' => 'Proje Takibi',
            'icon' => 'fas fa-project-diagram',
            'description' => 'Proje yönetimi'
        ],
        'reports' => [
            'name' => 'Raporlar',
            'icon' => 'fas fa-chart-bar',
            'description' => 'Raporlar ve analizler'
        ],
        'settings' => [
            'name' => 'Ayarlar',
            'icon' => 'fas fa-cog',
            'description' => 'Sistem ayarları'
        ]
    ];
    
    // Yetki seviyeleri
    const PERMISSION_LEVELS = [
        'none' => [
            'name' => 'Erişim Yok',
            'description' => 'Bu modüle erişim yok',
            'color' => 'danger'
        ],
        'read' => [
            'name' => 'Sadece Okuma',
            'description' => 'Sadece görüntüleme yetkisi',
            'color' => 'warning'
        ],
        'write' => [
            'name' => 'Okuma ve Yazma',
            'description' => 'Görüntüleme ve düzenleme yetkisi',
            'color' => 'success'
        ],
        'admin' => [
            'name' => 'Tam Yetki',
            'description' => 'Tüm yetkiler (silme dahil)',
            'color' => 'primary'
        ]
    ];
    
    // Demo kullanıcı yetkileri
    const DEMO_USER_PERMISSIONS = [
        'superadmin' => [
            'role' => 'superadmin',
            'permissions' => [
                'dashboard' => 'admin',
                'users' => 'admin',
                'permissions' => 'admin',
                'announcements' => 'admin',
                'events' => 'admin',
                'calendar' => 'admin',
                'inventory' => 'admin',
                'meeting_reports' => 'admin',
                'reservations' => 'admin',
                'expenses' => 'admin',
                'projects' => 'admin',
                'reports' => 'admin',
                'settings' => 'admin'
            ]
        ]
    ];
    
    /**
     * Tüm modülleri getir
     */
    public static function getModules() {
        return self::MODULES;
    }
    
    /**
     * Yetki seviyelerini getir
     */
    public static function getPermissionLevels() {
        return self::PERMISSION_LEVELS;
    }
    
    /**
     * Kullanıcının modül yetkisini kontrol et
     */
    public static function hasPermission($username, $module) {
        if (!isset(self::DEMO_USER_PERMISSIONS[$username])) {
            return false;
        }
        
        $userPermissions = self::DEMO_USER_PERMISSIONS[$username]['permissions'];
        return isset($userPermissions[$module]) && $userPermissions[$module] !== 'none';
    }
    
    /**
     * Kullanıcının modül yetki seviyesini getir
     */
    public static function getPermissionLevel($username, $module) {
        if (!isset(self::DEMO_USER_PERMISSIONS[$username])) {
            return 'none';
        }
        
        $userPermissions = self::DEMO_USER_PERMISSIONS[$username]['permissions'];
        return isset($userPermissions[$module]) ? $userPermissions[$module] : 'none';
    }
    
    /**
     * Kullanıcının tüm yetkilerini getir
     */
    public static function getUserPermissions($username) {
        return isset(self::DEMO_USER_PERMISSIONS[$username]) ? 
               self::DEMO_USER_PERMISSIONS[$username]['permissions'] : [];
    }
    
    /**
     * Kullanıcının görüntüleyebileceği modülleri getir
     */
    public static function getVisibleModules($username) {
        $permissions = self::getUserPermissions($username);
        $visibleModules = [];
        
        foreach (self::MODULES as $moduleKey => $moduleData) {
            if (isset($permissions[$moduleKey]) && $permissions[$moduleKey] !== 'none') {
                $visibleModules[$moduleKey] = array_merge($moduleData, [
                    'permission_level' => $permissions[$moduleKey],
                    'permission_data' => self::PERMISSION_LEVELS[$permissions[$moduleKey]]
                ]);
            }
        }
        
        return $visibleModules;
    }
    
    /**
     * Kullanıcının yazma yetkisi var mı kontrol et
     */
    public static function canWrite($username, $module) {
        $level = self::getPermissionLevel($username, $module);
        return in_array($level, ['write', 'admin']);
    }
    
    /**
     * Kullanıcının admin yetkisi var mı kontrol et
     */
    public static function canAdmin($username, $module) {
        $level = self::getPermissionLevel($username, $module);
        return $level === 'admin';
    }
    
    /**
     * Sidebar için modül linklerini oluştur
     */
    public static function generateSidebarLinks($username) {
        $visibleModules = self::getVisibleModules($username);
        $links = [];
        
        foreach ($visibleModules as $moduleKey => $moduleData) {
            $fileMap = [
                'dashboard' => 'dashboard_member.php',
                'users' => 'users.php',
                'permissions' => 'permissions.php',
                'announcements' => 'announcements.php',
                'events' => 'events.php',
                'calendar' => 'calendar.php',
                'inventory' => 'inventory.php',
                'meeting_reports' => 'meeting_reports.php',
                'reservations' => 'reservations.php',
                'expenses' => 'expenses.php',
                'projects' => 'projects.php',
                'reports' => 'reports.php',
                'settings' => 'settings.php'
            ];
            
            if (isset($fileMap[$moduleKey])) {
                $links[] = [
                    'file' => $fileMap[$moduleKey],
                    'name' => $moduleData['name'],
                    'icon' => $moduleData['icon'],
                    'permission_level' => $moduleData['permission_level'],
                    'permission_data' => $moduleData['permission_data']
                ];
            }
        }
        
        return $links;
    }
    
    /**
     * Yetki badge'i için HTML oluştur
     */
    public static function generatePermissionBadge($level) {
        $permissionData = self::PERMISSION_LEVELS[$level];
        return '<span class="badge bg-' . $permissionData['color'] . '">' . $permissionData['name'] . '</span>';
    }
}
?>
