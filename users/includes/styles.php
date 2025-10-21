/* Sidebar Styles - Tüm admin sayfalarında kullanılacak */
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

/* Sidebar Styles */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: var(--sidebar-width);
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    z-index: 1000;
    transition: all 0.3s ease;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
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
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
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

/* Hamburger Menu Styles */
.hamburger-menu {
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 1001;
    background: var(--primary-color);
    border: none;
    border-radius: 8px;
    padding: 12px;
    cursor: pointer;
    display: none;
    flex-direction: column;
    justify-content: space-around;
    width: 50px;
    height: 50px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
}

.hamburger-menu:hover {
    background: var(--primary-dark);
    transform: scale(1.05);
}

.hamburger-line {
    width: 25px;
    height: 3px;
    background: white;
    border-radius: 2px;
    transition: all 0.3s ease;
    transform-origin: center;
}

.hamburger-menu.active .hamburger-line:nth-child(1) {
    transform: rotate(45deg) translate(6px, 6px);
}

.hamburger-menu.active .hamburger-line:nth-child(2) {
    opacity: 0;
}

.hamburger-menu.active .hamburger-line:nth-child(3) {
    transform: rotate(-45deg) translate(6px, -6px);
}

/* Mobile Overlay */
.mobile-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.mobile-overlay.overlay-active {
    opacity: 1;
    visibility: visible;
}

/* Sidebar Close Button */
.sidebar-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: none;
    border: none;
    color: white;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 5px;
    border-radius: 50%;
    transition: all 0.3s ease;
    display: none;
}

.sidebar-close:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: scale(1.1);
}

/* Sidebar Header için padding ayarı */
.sidebar-header {
    position: relative;
    padding: 1.5rem 1rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

/* Responsive */
@media (max-width: 768px) {
    .hamburger-menu {
        display: flex;
    }

    .sidebar {
        transform: translateX(-100%);
        width: 280px;
    }

    .sidebar.sidebar-open {
        transform: translateX(0);
    }

    .sidebar-close {
        display: block;
    }

    .main-content {
        margin-left: 0;
        padding-top: 80px; /* Hamburger menü için boşluk */
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

    /* Body scroll engelleme */
    body.sidebar-open {
        overflow: hidden;
    }
}

@media (max-width: 480px) {
    .hamburger-menu {
        top: 15px;
        left: 15px;
        width: 45px;
        height: 45px;
        padding: 10px;
    }

    .hamburger-line {
        width: 22px;
        height: 2px;
    }

    .sidebar {
        width: 100%;
    }

    .main-content {
        padding-top: 75px;
    }
}