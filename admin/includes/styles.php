/* Özel Superadmin Sidebar Styles */
.custom-sidebar {
    background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460);
    box-shadow: 2px 0 20px rgba(0,0,0,0.3);
}

.custom-sidebar .sidebar-header {
    padding: 1.5rem 1rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    background: rgba(255,255,255,0.05);
}

.custom-sidebar .sidebar-logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.custom-sidebar .sidebar-logo i {
    font-size: 1.5rem;
    color: #ffd700;
}

.custom-sidebar .sidebar-logo h4 {
    color: white;
    margin: 0;
    font-weight: 700;
    font-size: 1.2rem;
}

.custom-sidebar .sidebar-user {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: rgba(255,255,255,0.1);
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.2);
}

.custom-sidebar .user-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #ffd700, #ffed4e);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1a1a2e;
    font-size: 1.1rem;
}

.custom-sidebar .user-info h6 {
    color: white;
    margin: 0;
    font-weight: 600;
    font-size: 0.9rem;
}

.custom-sidebar .user-info small {
    color: rgba(255,255,255,0.7);
    font-size: 0.75rem;
}

.custom-sidebar .nav-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    border-radius: 8px;
    margin: 0.25rem 0.5rem;
    transition: all 0.3s ease;
    position: relative;
}

.custom-sidebar .nav-link:hover {
    background: rgba(255,255,255,0.1);
    color: white;
    transform: translateX(5px);
}

.custom-sidebar .nav-link.active {
    background: linear-gradient(135deg, #009872, #00b085);
    color: white;
    box-shadow: 0 4px 15px rgba(0,152,114,0.3);
}

.custom-sidebar .nav-link i {
    width: 20px;
    text-align: center;
    font-size: 1rem;
}

.custom-sidebar .nav-link span {
    flex: 1;
    font-weight: 500;
}

.custom-sidebar .nav-link .badge {
    background: rgba(255,255,255,0.2);
    color: white;
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-weight: 600;
}

.custom-sidebar .nav-link.active .badge {
    background: rgba(255,255,255,0.3);
}

.custom-sidebar .nav-divider {
    padding: 0.5rem 1rem;
    margin: 1rem 0.5rem 0.5rem;
    color: rgba(255,255,255,0.5);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-top: 1px solid rgba(255,255,255,0.1);
    padding-top: 1rem;
}

.custom-sidebar .logout-link {
    background: rgba(220,53,69,0.2);
    border: 1px solid rgba(220,53,69,0.3);
    color: #ff6b6b !important;
}

.custom-sidebar .logout-link:hover {
    background: rgba(220,53,69,0.3);
    color: #ff5252 !important;
    transform: translateX(5px);
}

.custom-sidebar .logout-link i {
    color: #ff6b6b;
}

.custom-sidebar .logout-link:hover i {
    color: #ff5252;
}

:root {
    --primary-color: #009872;
    --primary-dark: #007a5e;
    --primary-light: #00b085;
    --sidebar-width: 250px;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f8f9fa;
}

/* Özel Sidebar Styles */
.sidebar.custom-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: var(--sidebar-width);
    z-index: 1000;
    transition: all 0.3s ease;
}

.sidebar-menu {
    padding: 1rem 0;
    height: calc(100vh - 80px);
    overflow-y: auto;
    overflow-x: hidden;
}

/* Sidebar scrollbar styling */
.sidebar-menu::-webkit-scrollbar {
    width: 6px;
}

.sidebar-menu::-webkit-scrollbar-track {
    background: rgba(255,255,255,0.1);
    border-radius: 3px;
}

.sidebar-menu::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.3);
    border-radius: 3px;
}

.sidebar-menu::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.5);
}

.sidebar-menu ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-menu .nav-item {
    margin: 0;
}

/* Main Content */
.main-content {
    margin-left: var(--sidebar-width);
    min-height: 100vh;
    transition: all 0.3s ease;
}

/* Header */
.header {
    background: white;
    padding: 1rem 2rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 999;
    margin-left: var(--sidebar-width);
    transition: margin-left 0.3s ease;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.header-title h1 {
    color: var(--primary-color);
    margin: 0;
    font-size: 1.8rem;
    font-weight: 600;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.navbar-toggler {
    background: none;
    border: none;
    font-size: 1.2rem;
    color: var(--primary-color);
    cursor: pointer;
}

.navbar-toggler:hover {
    color: var(--primary-dark);
}

/* Mobile responsive */
@media (max-width: 768px) {
    .header {
        margin-left: 0;
    }
    
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.show {
        transform: translateX(0);
    }
}

/* Content Area */
.content-area {
    padding: 2rem;
}

/* Stats Cards */
.stats-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
    border-left: 4px solid var(--primary-color);
}

.stats-card:hover {
    transform: translateY(-2px);
}

.stats-card .icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
    font-size: 1.5rem;
    color: white;
}

.stats-card .icon.users {
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.stats-card .icon.announcements {
    background: linear-gradient(135deg, #f093fb, #f5576c);
}

.stats-card .icon.events {
    background: linear-gradient(135deg, #4facfe, #00f2fe);
}

.stats-card .icon.refunds {
    background: linear-gradient(135deg, #43e97b, #38f9d7);
}

.stats-card h3 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #333;
}

.stats-card p {
    color: #666;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.stats-card small {
    color: #999;
    font-size: 0.85rem;
}

/* BYK Stats Cards */
.byk-at-border {
    border-left-color: #dc3545 !important;
}

.byk-kt-border {
    border-left-color: #6f42c1 !important;
}

.byk-kgt-border {
    border-left-color: #198754 !important;
}

.byk-gt-border {
    border-left-color: #0d6efd !important;
}

/* Page Cards */
.page-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
}

.page-card .card-header {
    padding: 1.5rem 1.5rem 0;
    border-bottom: none;
    background: transparent;
}

.page-card .card-header h5 {
    color: #333;
    font-weight: 600;
    margin: 0;
}

.page-card .card-body {
    padding: 1.5rem;
}

/* Chart Container */
.chart-container {
    height: 300px;
    position: relative;
}

/* Tables */
.table {
    margin-bottom: 0;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #555;
    background: #f8f9fa;
}

.table td {
    vertical-align: middle;
}

/* Badges */
.badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
}

/* Buttons */
.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

/* Dropdown */
.dropdown-menu {
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border: none;
}

.dropdown-item {
    padding: 0.5rem 1rem;
    transition: all 0.3s ease;
}

.dropdown-item:hover {
    background: #f8f9fa;
}

/* Responsive */
@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
    }
    
    .content-area {
        padding: 1rem;
    }
    
    .stats-card {
        margin-bottom: 1rem;
    }
    
    .header {
        padding: 1rem;
    }
    
    .header-title h1 {
        font-size: 1.5rem;
    }
}