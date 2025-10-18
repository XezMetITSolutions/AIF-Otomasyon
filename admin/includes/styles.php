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

/* Generic Sidebar Styles - Gizli */
.sidebar:not(.custom-sidebar) {
    display: none !important;
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

.sidebar-header {
    padding: 1.5rem 1rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-header h4 {
    color: white;
    margin: 0;
    font-weight: 600;
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

/* Firefox scrollbar styling */
.sidebar-menu {
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.3) rgba(255,255,255,0.1);
}

.sidebar-menu .nav-link {
    color: rgba(255,255,255,0.8);
    padding: 0.75rem 1.5rem;
    border-radius: 0;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.sidebar-menu .nav-link:hover,
.sidebar-menu .nav-link.active {
    color: white;
    background-color: rgba(255,255,255,0.1);
    border-left-color: white;
}

.sidebar-menu .nav-link i {
    width: 20px;
    margin-right: 10px;
    color: white;
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

/* Cards */
.page-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    border: none;
    margin-bottom: 2rem;
}

.page-card .card-header {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    border-radius: 15px 15px 0 0;
    padding: 1rem 1.5rem;
    border: none;
}

.page-card .card-header h5 {
    margin: 0;
    font-weight: 600;
}

/* Stats Cards */
.stats-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
    border: none;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.stats-card .icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    margin: 0 auto 1rem;
}

.stats-card .icon.users { background: linear-gradient(135deg, #007bff, #0056b3); }
.stats-card .icon.announcements { background: linear-gradient(135deg, #28a745, #1e7e34); }
.stats-card .icon.events { background: linear-gradient(135deg, #ffc107, #e0a800); }
.stats-card .icon.reservations { background: linear-gradient(135deg, #dc3545, #c82333); }

.stats-card h3 {
    color: var(--primary-color);
    margin-bottom: 0.5rem;
    font-weight: 700;
}

.stats-card p {
    color: #6c757d;
    margin: 0;
}

/* Tables */
.table-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    overflow: hidden;
}

.table-card .table {
    margin: 0;
}

.table-card .table th {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 1rem;
    font-weight: 600;
}

.table-card .table td {
    padding: 1rem;
    border-color: #e9ecef;
    vertical-align: middle;
}

.table-card .table tbody tr:hover {
    background-color: rgba(0, 152, 114, 0.05);
}

/* Status Badges */
.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-pending { background-color: #ffc107; color: #000; }
.status-approved { background-color: #28a745; color: white; }
.status-rejected { background-color: #dc3545; color: white; }
.status-active { background-color: #17a2b8; color: white; }

/* Charts */
.chart-container {
    position: relative;
    height: 300px;
    padding: 1rem;
}

/* Buttons */
.btn-primary {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-primary:hover {
    background-color: var(--primary-dark);
    border-color: var(--primary-dark);
}

.btn-outline-primary {
    color: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-outline-primary:hover {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

/* BYK Renk Kodları */
.byk-at {
    background-color: #dc3545 !important;
    color: white !important;
}

.byk-at-light {
    background-color: #f8d7da !important;
    color: #721c24 !important;
}

.byk-at-border {
    border-left: 4px solid #dc3545 !important;
}

.byk-kt {
    background-color: #6f42c1 !important;
    color: white !important;
}

.byk-kt-light {
    background-color: #e2d9f3 !important;
    color: #432874 !important;
}

.byk-kt-border {
    border-left: 4px solid #6f42c1 !important;
}

.byk-kgt {
    background-color: #198754 !important;
    color: white !important;
}

.byk-kgt-light {
    background-color: #d1e7dd !important;
    color: #0f5132 !important;
}

.byk-kgt-border {
    border-left: 4px solid #198754 !important;
}

.byk-gt {
    background-color: #0d6efd !important;
    color: white !important;
}

.byk-gt-light {
    background-color: #cfe2ff !important;
    color: #084298 !important;
}

.byk-gt-border {
    border-left: 4px solid #0d6efd !important;
}

/* BYK Badge Stilleri */
.badge.byk-at { background-color: #dc3545 !important; }
.badge.byk-kt { background-color: #6f42c1 !important; }
.badge.byk-kgt { background-color: #198754 !important; }
.badge.byk-gt { background-color: #0d6efd !important; }

/* BYK Card Stilleri */
.card.byk-at { border-left: 4px solid #dc3545; }
.card.byk-kt { border-left: 4px solid #6f42c1; }
.card.byk-kgt { border-left: 4px solid #198754; }
.card.byk-gt { border-left: 4px solid #0d6efd; }

/* BYK Text Stilleri */
.text-byk-at { color: #dc3545 !important; }
.text-byk-kt { color: #6f42c1 !important; }
.text-byk-kgt { color: #198754 !important; }
.text-byk-gt { color: #0d6efd !important; }

/* Responsive */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }

    .sidebar.show {
        transform: translateX(0);
    }

    .main-content {
        margin-left: 0;
    }

    .content-area {
        padding: 1rem;
    }

    .header {
        padding: 1rem;
    }

    .header-title h1 {
        font-size: 1.5rem;
    }
}
