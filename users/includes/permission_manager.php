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
        'manager' => [
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
                'dashboard' => 'manager',
                'users' => 'manager',
                'permissions' => 'manager',
                'announcements' => 'manager',
                'events' => 'manager',
                'calendar' => 'manager',
                'inventory' => 'manager',
                'meeting_reports' => 'manager',
                'reservations' => 'manager',
                'expenses' => 'manager',
                'projects' => 'manager',
                'reports' => 'manager',
                'settings' => 'manager'
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
        $level = self::getPermissionLevel($username, $module);
        return $level !== 'none';
    }
    
    /**
     * Kullanıcının modül yetki seviyesini getir
     */
    public static function getPermissionLevel($username, $module) {
        try {
            require_once 'user_manager_db.php';
            $db = Database::getInstance();
            
            // Kullanıcı ID'sini al
            $user = $db->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
            if (!$user) {
                return 'none';
            }
            
            // Modül yetkisini veritabanından çek
            $permission = $db->fetchOne(
                "SELECT up.can_read, up.can_write, up.can_admin 
                 FROM user_permissions up 
                 JOIN modules m ON up.module_id = m.id 
                 WHERE up.user_id = ? AND m.name = ?",
                [$user['id'], $module]
            );
            
            if (!$permission) {
                return 'none';
            }
            
            if ($permission['can_admin']) {
                return 'manager';
            } elseif ($permission['can_write']) {
                return 'write';
            } elseif ($permission['can_read']) {
                return 'read';
            }
            
            return 'none';
        } catch (Exception $e) {
            error_log("getPermissionLevel error: " . $e->getMessage());
            // Hata durumunda demo verileri döndür
        if (!isset(self::DEMO_USER_PERMISSIONS[$username])) {
            return 'none';
        }
        
        $userPermissions = self::DEMO_USER_PERMISSIONS[$username]['permissions'];
        return isset($userPermissions[$module]) ? $userPermissions[$module] : 'none';
        }
    }
    
    /**
     * Kullanıcının tüm yetkilerini getir
     */
    public static function getUserPermissions($username) {
        try {
            require_once 'user_manager_db.php';
            $db = Database::getInstance();
            
            // Kullanıcı ID'sini al
            $user = $db->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
            if (!$user) {
                return [];
            }
            
            // Kullanıcının yetkilerini veritabanından çek
            $permissions = $db->fetchAll(
                "SELECT m.name, up.can_read, up.can_write, up.can_admin 
                 FROM user_permissions up 
                 JOIN modules m ON up.module_id = m.id 
                 WHERE up.user_id = ?",
                [$user['id']]
            );
            
            $result = [];
            foreach ($permissions as $permission) {
                $level = 'none';
                if ($permission['can_admin']) {
                    $level = 'manager';
                } elseif ($permission['can_write']) {
                    $level = 'write';
                } elseif ($permission['can_read']) {
                    $level = 'read';
                }
                
                $result[$permission['name']] = $level;
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("getUserPermissions error: " . $e->getMessage());
            // Hata durumunda demo verileri döndür
        return isset(self::DEMO_USER_PERMISSIONS[$username]) ? 
               self::DEMO_USER_PERMISSIONS[$username]['permissions'] : [];
        }
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
        return in_array($level, ['write', 'manager']);
    }
    
    /**
     * Kullanıcının admin yetkisi var mı kontrol et
     */
    public static function canAdmin($username, $module) {
        $level = self::getPermissionLevel($username, $module);
        return $level === 'manager';
    }

    public static function canView(string $username, string $module): bool {
        $level = self::getPermissionLevel($username, $module);
        return in_array($level, ['read', 'write', 'manager'], true);
    }

    public static function canEdit(string $username, string $module): bool {
        return self::canAdmin($username, $module);
    }

    public static function requireModuleAccess(string $module, string $minLevel = 'read'): void {
        if (!class_exists('SessionManager')) {
            require_once dirname(__DIR__) . '/auth.php';
        }
        $currentUser = SessionManager::getCurrentUser();
        if (!$currentUser) {
            header('Location: ../index.php');
            exit;
        }

        $username = $currentUser['username'];
        $level = self::getPermissionLevel($username, $module);

        $ok = $minLevel === 'read'
            ? in_array($level, ['read', 'write', 'manager'], true)
            : ($level === 'manager');

        if (!$ok) {
            if ($currentUser['role'] === 'superadmin' || $currentUser['role'] === 'manager') {
                header('Location: ../admin/dashboard_superadmin.php');
            } else {
                header('Location: dashboard_member.php');
            }
            exit;
        }
    }

    /**
     * Varsayılan yetki profilleri
     */
    public static function getDefaultPermissions(string $role): array {
        $profiles = [
            'member' => [
                'dashboard' => 'read',
                'calendar' => 'read',
                'meeting_reports' => 'read',
                'reservations' => 'read',
                'expenses' => 'read',
                'announcements' => 'read',
                'events' => 'read'
            ],
            'manager' => [
                'dashboard' => 'manager',
                'users' => 'manager',
                'permissions' => 'manager',
                'calendar' => 'manager',
                'meeting_reports' => 'manager',
                'reservations' => 'manager',
                'expenses' => 'manager',
                'announcements' => 'manager',
                'events' => 'manager',
                'inventory' => 'manager',
                'projects' => 'manager',
                'reports' => 'manager',
                'settings' => 'manager'
            ],
            'superadmin' => [
                'dashboard' => 'manager',
                'users' => 'manager',
                'permissions' => 'manager',
                'calendar' => 'manager',
                'meeting_reports' => 'manager',
                'reservations' => 'manager',
                'expenses' => 'manager',
                'announcements' => 'manager',
                'events' => 'manager',
                'inventory' => 'manager',
                'projects' => 'manager',
                'reports' => 'manager',
                'settings' => 'manager'
            ]
        ];

        return $profiles[$role] ?? [];
    }

    /**
     * Kullanıcıya varsayılan yetkileri ata
     */
    public static function assignDefaultPermissions(int $userId, string $role): bool {
        try {
            require_once '../admin/includes/user_manager_db.php';
            $db = Database::getInstance();
            
            $defaultPermissions = self::getDefaultPermissions($role);
            
            foreach ($defaultPermissions as $moduleName => $level) {
                // Modül ID'sini bul
                $module = $db->fetchOne("SELECT id FROM modules WHERE name = ?", [$moduleName]);
                if (!$module) continue;
                
                $canRead = in_array($level, ['read', 'write', 'manager']);
                $canWrite = in_array($level, ['write', 'manager']);
                $canAdmin = ($level === 'manager');
                
                $permissionData = [
                    'user_id' => $userId,
                    'module_id' => $module['id'],
                    'can_read' => $canRead,
                    'can_write' => $canWrite,
                    'can_admin' => $canAdmin,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $db->insert('user_permissions', $permissionData);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("assignDefaultPermissions error: " . $e->getMessage());
            return false;
        }
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

            }
        }
        
        return $visibleModules;
    }
    
    /**
     * Kullanıcının yazma yetkisi var mı kontrol et
     */
    public static function canWrite($username, $module) {
        $level = self::getPermissionLevel($username, $module);
        return in_array($level, ['write', 'manager']);
    }
    
    /**
     * Kullanıcının admin yetkisi var mı kontrol et
     */
    public static function canAdmin($username, $module) {
        $level = self::getPermissionLevel($username, $module);
        return $level === 'manager';
    }

    public static function canView(string $username, string $module): bool {
        $level = self::getPermissionLevel($username, $module);
        return in_array($level, ['read', 'write', 'manager'], true);
    }

    public static function canEdit(string $username, string $module): bool {
        return self::canAdmin($username, $module);
    }

    public static function requireModuleAccess(string $module, string $minLevel = 'read'): void {
        if (!class_exists('SessionManager')) {
            require_once dirname(__DIR__) . '/auth.php';
        }
        $currentUser = SessionManager::getCurrentUser();
        if (!$currentUser) {
            header('Location: ../index.php');
            exit;
        }

        $username = $currentUser['username'];
        $level = self::getPermissionLevel($username, $module);

        $ok = $minLevel === 'read'
            ? in_array($level, ['read', 'write', 'manager'], true)
            : ($level === 'manager');

        if (!$ok) {
            if ($currentUser['role'] === 'superadmin' || $currentUser['role'] === 'manager') {
                header('Location: ../admin/dashboard_superadmin.php');
            } else {
                header('Location: dashboard_member.php');
            }
            exit;
        }
    }

    /**
     * Varsayılan yetki profilleri
     */
    public static function getDefaultPermissions(string $role): array {
        $profiles = [
            'member' => [
                'dashboard' => 'read',
                'calendar' => 'read',
                'meeting_reports' => 'read',
                'reservations' => 'read',
                'expenses' => 'read',
                'announcements' => 'read',
                'events' => 'read'
            ],
            'manager' => [
                'dashboard' => 'manager',
                'users' => 'manager',
                'permissions' => 'manager',
                'calendar' => 'manager',
                'meeting_reports' => 'manager',
                'reservations' => 'manager',
                'expenses' => 'manager',
                'announcements' => 'manager',
                'events' => 'manager',
                'inventory' => 'manager',
                'projects' => 'manager',
                'reports' => 'manager',
                'settings' => 'manager'
            ],
            'superadmin' => [
                'dashboard' => 'manager',
                'users' => 'manager',
                'permissions' => 'manager',
                'calendar' => 'manager',
                'meeting_reports' => 'manager',
                'reservations' => 'manager',
                'expenses' => 'manager',
                'announcements' => 'manager',
                'events' => 'manager',
                'inventory' => 'manager',
                'projects' => 'manager',
                'reports' => 'manager',
                'settings' => 'manager'
            ]
        ];

        return $profiles[$role] ?? [];
    }

    /**
     * Kullanıcıya varsayılan yetkileri ata
     */
    public static function assignDefaultPermissions(int $userId, string $role): bool {
        try {
            require_once '../admin/includes/user_manager_db.php';
            $db = Database::getInstance();
            
            $defaultPermissions = self::getDefaultPermissions($role);
            
            foreach ($defaultPermissions as $moduleName => $level) {
                // Modül ID'sini bul
                $module = $db->fetchOne("SELECT id FROM modules WHERE name = ?", [$moduleName]);
                if (!$module) continue;
                
                $canRead = in_array($level, ['read', 'write', 'manager']);
                $canWrite = in_array($level, ['write', 'manager']);
                $canAdmin = ($level === 'manager');
                
                $permissionData = [
                    'user_id' => $userId,
                    'module_id' => $module['id'],
                    'can_read' => $canRead,
                    'can_write' => $canWrite,
                    'can_admin' => $canAdmin,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $db->insert('user_permissions', $permissionData);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("assignDefaultPermissions error: " . $e->getMessage());
            return false;
        }
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
