<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('title') ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">
    
    <style>
        :root {
            /* Dark Neon Theme */
            --bg-primary: #0a0a0f;
            --bg-secondary: #12121a;
            --bg-card: rgba(20, 20, 35, 0.8);
            --bg-sidebar: #0d0d15;
            
            /* Neon Colors */
            --neon-cyan: #00f5ff;
            --neon-purple: #bf00ff;
            --neon-pink: #ff00aa;
            --neon-green: #00ff88;
            --neon-orange: #ff6b35;
            
            /* Gradients */
            --gradient-primary: linear-gradient(135deg, #00f5ff 0%, #bf00ff 100%);
            --gradient-success: linear-gradient(135deg, #00ff88 0%, #00d4aa 100%);
            --gradient-warning: linear-gradient(135deg, #ff6b35 0%, #ff8c42 100%);
            
            /* Text */
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.6);
            --text-muted: rgba(255, 255, 255, 0.4);
            
            /* Border */
            --border-color: rgba(255, 255, 255, 0.08);
            --border-glow: rgba(0, 245, 255, 0.3);
            
            /* Shadows */
            --shadow-neon: 0 0 20px rgba(0, 245, 255, 0.3);
            --shadow-card: 0 8px 32px rgba(0, 0, 0, 0.4);
            
            /* Sidebar */
            --sidebar-width: 260px;
            --sidebar-collapsed: 70px;
        }

        /* Light Theme Overrides */
        [data-theme="light"] {
            --bg-primary: #f4f6f8;
            --bg-secondary: #ffffff;
            --bg-card: #ffffff;
            --bg-sidebar: #ffffff;
            
            --neon-cyan: #008ba3; 
            --neon-purple: #7b1fa2; 
            --neon-pink: #d81b60; 
            --neon-green: #2e7d32;
            --neon-orange: #f57c00;
            --neon-red: #d32f2f;
            
            --gradient-primary: linear-gradient(135deg, #0288d1 0%, #7b1fa2 100%);
            
            --text-primary: #1a1a1a;
            --text-secondary: #4a4a4a;
            --text-muted: #757575;
            
            --border-color: #e0e0e0;
            --border-glow: rgba(0,0,0,0.1);
            
            --shadow-neon: 0 0 10px rgba(0, 139, 163, 0.3);
            --shadow-card: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        /* Light Theme Fixes */
        [data-theme="light"] .sidebar {
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            border-right: 1px solid var(--border-color);
        }
        [data-theme="light"] .menu-item:hover, 
        [data-theme="light"] .submenu-item:hover {
            background: #f5f5f5;
        }
        [data-theme="light"] .form-control {
            background: #fff;
            color: #333;
            border: 1px solid #ddd;
        }
        [data-theme="light"] .datatable-selector, 
        [data-theme="light"] .datatable-input,
        [data-theme="light"] .datatable-table {
            background: #fff !important;
            color: #333 !important;
            border-color: #e0e0e0 !important;
        }
        [data-theme="light"] .top-header { background: #ffffff; }
        [data-theme="light"] .sidebar-toggle { background: #eee; border-color: #ddd; color: #555; }
        [data-theme="light"] .header-btn { background: #f0f0f0; border-color: #ddd; color: #555; }
        [data-theme="light"] .btn-secondary { background: #fff; border-color: #ddd; color: #333; }
        [data-theme="light"] .badge-success { background: #e8f5e9; color: #2e7d32; }
        [data-theme="light"] .badge-danger { background: #ffebee; color: #c62828; }
        [data-theme="light"] .mobile-toggle { color: #333; }
        [data-theme="light"] .data-table tr:hover { background: #f9f9f9; }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
        }
        
        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border-color);
            position: fixed;
            left: 0;
            top: 0;
            display: flex;
            flex-direction: column;
            transition: width 0.3s ease;
            z-index: 1000;
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-collapsed);
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .sidebar-logo {
            width: 40px;
            height: 40px;
            background: var(--gradient-primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: 700;
            flex-shrink: 0;
        }
        
        .sidebar-title {
            font-size: 1.25rem;
            font-weight: 700;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            white-space: nowrap;
        }
        
        .sidebar.collapsed .sidebar-title,
        .sidebar.collapsed .menu-text,
        .sidebar.collapsed .menu-badge {
            display: none;
        }
        
        .sidebar-menu {
            flex: 1;
            padding: 1rem 0;
            overflow-y: auto;
        }
        
        .menu-section {
            padding: 0.5rem 1rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-top: 0.5rem;
        }
        
        .sidebar.collapsed .menu-section {
            text-align: center;
            padding: 0.5rem 0;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1.25rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            margin: 0.125rem 0;
        }
        
        .menu-item:hover {
            background: rgba(0, 245, 255, 0.05);
            color: var(--neon-cyan);
            border-left-color: var(--neon-cyan);
        }
        
        .menu-item.active {
            background: rgba(0, 245, 255, 0.1);
            color: var(--neon-cyan);
            border-left-color: var(--neon-cyan);
        }
        
        .menu-icon {
            width: 20px;
            text-align: center;
            font-size: 1rem;
        }
        
        .menu-text {
            flex: 1;
            font-size: 0.9rem;
        }
        
        .menu-badge {
            background: var(--gradient-primary);
            padding: 0.125rem 0.5rem;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        /* Submenu Styles */
        .menu-item-parent {
            cursor: pointer;
        }
        
        .menu-arrow {
            margin-left: auto;
            font-size: 0.7rem;
            transition: transform 0.2s ease;
        }
        
        .menu-item-parent.open .menu-arrow {
            transform: rotate(90deg);
        }
        
        .menu-submenu {
            display: none;
            background: rgba(0, 0, 0, 0.2);
            padding-left: 0;
        }
        
        .menu-submenu.active {
            display: block;
        }
        
        .submenu-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 1.25rem 0.65rem 2.5rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }
        
        .submenu-item:hover {
            color: var(--text-primary);
            background: rgba(0, 245, 255, 0.05);
        }
        
        .submenu-item.active {
            color: var(--neon-cyan);
            border-left-color: var(--neon-cyan);
            background: rgba(0, 245, 255, 0.1);
        }
        
        .submenu-item i {
            width: 16px;
            text-align: center;
            font-size: 0.8rem;
        }
        
        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid var(--border-color);
        }
        
        .sidebar-toggle {
            width: 100%;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .sidebar-toggle:hover {
            background: rgba(0, 245, 255, 0.1);
            color: var(--neon-cyan);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease;
            min-height: 100vh;
        }
        
        .sidebar.collapsed ~ .main-content {
            margin-left: var(--sidebar-collapsed);
        }
        
        /* Top Header */
        .top-header {
            height: 70px;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .header-btn {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .header-btn:hover {
            background: rgba(0, 245, 255, 0.1);
            color: var(--neon-cyan);
            border-color: var(--border-glow);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--gradient-primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            cursor: pointer;
        }
        
        /* Page Content */
        .page-content {
            padding: 2rem;
        }
        
        /* Cards */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-card);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            border-color: var(--border-glow);
            box-shadow: var(--shadow-neon);
            transform: translateY(-2px);
        }
        
        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-icon.cyan { background: rgba(0, 245, 255, 0.15); color: var(--neon-cyan); }
        .stat-icon.purple { background: rgba(191, 0, 255, 0.15); color: var(--neon-purple); }
        .stat-icon.green { background: rgba(0, 255, 136, 0.15); color: var(--neon-green); }
        .stat-icon.orange { background: rgba(255, 107, 53, 0.15); color: var(--neon-orange); }
        
        .stat-info h3 {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1;
        }
        
        .stat-info p {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .data-table th {
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .data-table tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }
        
        /* Badges */
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-success { background: rgba(0, 255, 136, 0.15); color: var(--neon-green); }
        .badge-warning { background: rgba(255, 107, 53, 0.15); color: var(--neon-orange); }
        .badge-info { background: rgba(0, 245, 255, 0.15); color: var(--neon-cyan); }
        .badge-danger { background: rgba(255, 0, 170, 0.15); color: var(--neon-pink); }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            border: none;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            color: var(--bg-primary);
        }
        
        .btn-primary:hover {
            box-shadow: var(--shadow-neon);
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .btn-sm {
            padding: 0.5rem 0.875rem;
            font-size: 0.8rem;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--neon-cyan);
            box-shadow: 0 0 0 3px rgba(0, 245, 255, 0.1);
        }
        
        .form-control::placeholder {
            color: var(--text-muted);
        }
        
        /* Select dropdown styling */
        select.form-control {
            background-color: #1a1f2e;
            color: #ffffff;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2300f5ff' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
        }
        
        select.form-control option {
            background-color: #1a1f2e;
            color: #ffffff;
            padding: 10px;
        }
        
        select.form-control option:checked,
        select.form-control option:hover {
            background-color: #0d1117;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .action-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
            text-align: center;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.3s;
        }
        
        .action-card:hover {
            background: rgba(0, 245, 255, 0.05);
            border-color: var(--border-glow);
            transform: translateY(-2px);
        }
        
        .action-card i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--neon-cyan);
        }
        
        .action-card span {
            display: block;
            font-size: 0.85rem;
        }
        
        /* Map Container */
        #map {
            height: 500px;
            border-radius: 12px;
            overflow: hidden;
        }
        
        /* Terminal */
        .terminal {
            background: #0d0d12;
            border-radius: 12px;
            padding: 1rem;
            font-family: 'Fira Code', monospace;
        }
        
        .terminal-input {
            display: flex;
            gap: 0.5rem;
        }
        
        .terminal-input input {
            flex: 1;
            background: transparent;
            border: 1px solid var(--border-color);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            color: var(--neon-green);
            font-family: inherit;
        }
        
        .terminal-output {
            margin-top: 1rem;
            color: var(--neon-green);
            min-height: 100px;
            white-space: pre-wrap;
        }
        
        /* Submenu Styles */
        .menu-item-parent {
            position: relative;
            cursor: pointer;
        }
        
        .menu-submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .menu-submenu.active {
            max-height: 500px;
        }
        
        .submenu-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.25rem 0.75rem 3.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 0.85rem;
        }
        
        .submenu-item:hover {
            background: rgba(0, 245, 255, 0.05);
            color: var(--neon-cyan);
        }
        
        .submenu-item.active {
            background: rgba(0, 245, 255, 0.1);
            color: var(--neon-cyan);
        }
        
        .menu-arrow {
            margin-left: auto;
            transition: transform 0.3s ease;
            font-size: 0.7rem;
        }
        
        .menu-item-parent.open .menu-arrow {
            transform: rotate(90deg);
        }
        
        .sidebar.collapsed .menu-submenu {
            display: none;
        }
        
        
            /* Responsive Mobile Styles */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width);
                z-index: 1050;
                box-shadow: 10px 0 20px rgba(0,0,0,0.5);
            }
            
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%; /* Ensure full width */
                overflow-x: hidden; /* Prevent page scroll horizontal */
            }
            
            .sidebar.collapsed ~ .main-content {
                margin-left: 0;
            }
            
            .top-header {
                padding: 0 1rem;
            }
            
            .page-content {
                padding: 1rem;
                overflow-x: hidden; /* Prevent content overflow */
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            /* Make grids inside settings page single column */
            div[style*="grid-template-columns: 1fr 1fr"] {
                grid-template-columns: 1fr !important;
            }
            
            /* TABLE RESPONSIVENESS FIX */
            .card {
                padding: 1rem;
                overflow: hidden; /* Prevent card overflow */
            }

            /* Wrapper for DataTables */
            .datatable-wrapper {
                width: 100%;
                overflow-x: auto; /* Scroll horizontal if absolutely needed */
                -webkit-overflow-scrolling: touch;
                margin: 0;
            }
            
            .datatable-table {
                width: 100% !important;
                /* min-width: 600px; REMOVED to allow fitting in screen if possible */
                table-layout: auto;
            }
            
            /* General Table Responsive */
            .table-responsive {
                display: block;
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            table {
                width: 100% !important;
            }
            
            /* Compact Data Cells for Mobile */
            .datatable-table th, .datatable-table td,
            table th, table td {
                white-space: normal !important; /* Force text wrap */
                word-wrap: break-word;
                font-size: 0.75rem !important; /* Smaller font (approx 12px) */
                padding: 0.5rem 0.25rem !important; /* Minimal padding */
                vertical-align: top;
            }
            
            /* Make action buttons smaller and stackable */
            table .btn, .datatable-table .btn {
                padding: 0.2rem 0.4rem !important;
                font-size: 0.7rem !important;
                display: inline-block;
                margin: 1px;
            }
            
            /* Hide less important columns on mobile if needed (add class .hidemobile manually to columns later if desired) */
            .hidemobile {
                display: none;
            }

            /* Responsive Utilities */
            .hidden-mobile {
                display: none !important;
            }
            
            .show-mobile {
                display: block !important;
            }
        }
        
        /* Overlay for mobile sidebar */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1040;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .sidebar-overlay.show {
            display: block;
            opacity: 1;
        }

        /* Hamburger Menu */
        .mobile-toggle {
            display: none;
            margin-right: 1rem;
            background: transparent;
            border: none;
            color: var(--text-primary);
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .mobile-toggle {
                display: block;
            }
        }
    </style>
    <script>
        // Init Theme from LocalStorage immediately to prevent flash
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">G</div>
            <span class="sidebar-title">Gembok</span>
        </div>
        
        <nav class="sidebar-menu">
            <?php $role = session()->get('admin_role'); ?>
            <div class="menu-section">Main Menu</div>
            
            <?php if ($role === 'technician'): ?>
            <a href="<?= base_url('admin/technician/dashboard') ?>" class="menu-item <?= uri_string() === 'admin/technician/dashboard' ? 'active' : '' ?>">
                <i class="fas fa-home menu-icon"></i>
                <span class="menu-text">Tugas Saya</span>
            </a>
            <?php else: ?>
            <a href="<?= base_url('admin') ?>" class="menu-item <?= uri_string() === 'admin' || uri_string() === 'admin/dashboard' ? 'active' : '' ?>">
                <i class="fas fa-home menu-icon"></i>
                <span class="menu-text">Dashboard</span>
            </a>
            
            <a href="<?= base_url('admin/analytics') ?>" class="menu-item <?= uri_string() === 'admin/analytics' ? 'active' : '' ?>">
                <i class="fas fa-chart-line menu-icon"></i>
                <span class="menu-text">Analytics</span>
            </a>
            <?php endif; ?>
            
            <div class="menu-section">Device Management</div>
            
            <?php 
            $genieUrl = ($role === 'technician') ? 'admin/technician/genieacs' : 'admin/genieacs';
            $mapUrl = ($role === 'technician') ? 'admin/technician/map' : 'admin/map';
            ?>

            <a href="<?= base_url($genieUrl) ?>" class="menu-item <?= uri_string() === $genieUrl ? 'active' : '' ?>">
                <i class="fas fa-server menu-icon"></i>
                <span class="menu-text">GenieACS</span>
            </a>
            
            <a href="<?= base_url($mapUrl) ?>" class="menu-item <?= uri_string() === $mapUrl ? 'active' : '' ?>">
                <i class="fas fa-map-marked-alt menu-icon"></i>
                <span class="menu-text">Map Monitoring</span>
            </a>
            
            <?php if ($role === 'admin'): ?>
            <div class="menu-section">MikroTik</div>
            
            <a href="<?= base_url('admin/mikrotik') ?>" class="menu-item <?= uri_string() === 'admin/mikrotik' ? 'active' : '' ?>">
                <i class="fas fa-network-wired menu-icon"></i>
                <span class="menu-text">PPPoE</span>
            </a>
            
            <a href="<?= base_url('admin/mikrotik/profiles') ?>" class="menu-item <?= uri_string() === 'admin/mikrotik/profiles' ? 'active' : '' ?>">
                <i class="fas fa-sliders-h menu-icon"></i>
                <span class="menu-text">Profile PPPoE</span>
            </a>
            
            <a href="<?= base_url('admin/mikrotik/hotspot-profiles') ?>" class="menu-item <?= uri_string() === 'admin/mikrotik/hotspot-profiles' ? 'active' : '' ?>">
                <i class="fas fa-wifi menu-icon"></i>
                <span class="menu-text">Profile Hotspot</span>
            </a>
            
            <a href="<?= base_url('admin/hotspot') ?>" class="menu-item <?= uri_string() === 'admin/hotspot' ? 'active' : '' ?>">
                <i class="fas fa-broadcast-tower menu-icon"></i>
                <span class="menu-text">Hotspot</span>
            </a>
            
            <a href="<?= base_url('admin/hotspot/voucher') ?>" class="menu-item <?= uri_string() === 'admin/hotspot/voucher' ? 'active' : '' ?>">
                <i class="fas fa-ticket-alt menu-icon"></i>
                <span class="menu-text">Voucher</span>
            </a>
            <?php endif; ?>
            
            <div class="menu-section">Billing & Support</div>
            
            <?php if ($role === 'technician'): ?>
            <a href="<?= base_url('admin/technician/dashboard') ?>" class="menu-item <?= uri_string() === 'admin/technician/dashboard' ? 'active' : '' ?>">
                <i class="fas fa-tools menu-icon"></i>
                <span class="menu-text">Tiket Laporan</span>
            </a>
            <?php else: ?>
            <a href="<?= base_url('admin/trouble') ?>" class="menu-item <?= uri_string() === 'admin/trouble' ? 'active' : '' ?>">
                <i class="fas fa-tools menu-icon"></i>
                <span class="menu-text">Tiket Gangguan</span>
            </a>

            <a href="<?= base_url('admin/technicians') ?>" class="menu-item <?= uri_string() === 'admin/technicians' ? 'active' : '' ?>">
                <i class="fas fa-user-shield menu-icon"></i>
                <span class="menu-text">Manajemen Teknisi</span>
            </a>
            <?php endif; ?>
            
            <?php if ($role === 'admin'): ?>
            <div class="menu-item menu-item-parent <?= strpos(uri_string(), 'billing') !== false ? 'open' : '' ?>" onclick="toggleSubmenu(this)">

                <i class="fas fa-file-invoice-dollar menu-icon"></i>
                <span class="menu-text">Billing</span>
                <i class="fas fa-chevron-right menu-arrow"></i>
            </div>
            <div class="menu-submenu <?= strpos(uri_string(), 'billing') !== false ? 'active' : '' ?>">
                <a href="<?= base_url('admin/billing/packages') ?>" class="submenu-item <?= uri_string() === 'admin/billing/packages' ? 'active' : '' ?>">
                    <i class="fas fa-box"></i>
                    <span>Paket</span>
                </a>
                <a href="<?= base_url('admin/billing/customers') ?>" class="submenu-item <?= uri_string() === 'admin/billing/customers' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    <span>Pelanggan</span>
                </a>
                <a href="<?= base_url('admin/billing/invoices') ?>" class="submenu-item <?= uri_string() === 'admin/billing/invoices' || uri_string() === 'admin/billing' || uri_string() === 'billing' ? 'active' : '' ?>">
                    <i class="fas fa-file-invoice"></i>
                    <span>Invoice</span>
                </a>
            </div>
            
            <div class="menu-section">System</div>
            
            <a href="<?= base_url('admin/setting') ?>" class="menu-item <?= uri_string() === 'admin/setting' || uri_string() === 'admin/settings' ? 'active' : '' ?>">
                <i class="fas fa-cog menu-icon"></i>
                <span class="menu-text">Setting</span>
            </a>
            
            <a href="<?= base_url('admin/update') ?>" class="menu-item <?= uri_string() === 'admin/update' ? 'active' : '' ?>">
                <i class="fas fa-cloud-download-alt menu-icon"></i>
                <span class="menu-text">Update</span>
                <span class="menu-badge" style="background: var(--neon-green); color: var(--bg-primary);">New</span>
            </a>
            <?php endif; ?>
        </nav>
        
        <div class="sidebar-footer">
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <header class="top-header">
            <button class="mobile-toggle" onclick="toggleSidebarMobile()">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="page-title"><?= $this->renderSection('page_title') ?></h1>
            <div class="header-actions">
                <button class="header-btn" onclick="toggleTheme()" title="Ganti Tema">
                    <i class="fas fa-moon theme-icon"></i>
                </button>
                <button class="header-btn" title="Notifications">
                    <i class="fas fa-bell"></i>
                </button>
                <button class="header-btn" title="Search">
                    <i class="fas fa-search"></i>
                </button>
                <div class="user-dropdown" style="position: relative;">
                    <div class="user-avatar" onclick="toggleUserMenu()" style="cursor: pointer;" title="<?= session()->get('admin_name') ?? 'Admin' ?>">
                        <?= strtoupper(substr(session()->get('admin_name') ?? 'A', 0, 1)) ?>
                    </div>
                    <div id="userMenu" class="user-menu" style="display: none; position: absolute; right: 0; top: 50px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; padding: 0.5rem 0; min-width: 180px; z-index: 200;">
                        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border-color);">
                            <div style="font-weight: 600;"><?= esc(session()->get('admin_name') ?? 'Admin') ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">@<?= esc(session()->get('admin_username') ?? 'admin') ?></div>
                        </div>
                        <a href="<?= base_url('admin/settings') ?>" style="display: block; padding: 0.75rem 1rem; color: var(--text-secondary); text-decoration: none;">
                            <i class="fas fa-cog" style="width: 20px;"></i> Pengaturan
                        </a>
                        <a href="<?= base_url('admin/logout') ?>" style="display: block; padding: 0.75rem 1rem; color: var(--neon-pink); text-decoration: none;">
                            <i class="fas fa-sign-out-alt" style="width: 20px;"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Loading Bar -->
        <div id="loadingBar" class="loading-bar"></div>
        
        <div class="page-content" id="pageContent">
            <?= $this->renderSection('content') ?>
        </div>
    </main>

    <!-- Mobile Sidebar Overlay -->
    <div class="sidebar-overlay" onclick="toggleSidebarMobile()"></div>
    
    <style>
        /* Loading Bar */
        .loading-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            background: var(--gradient-primary);
            width: 0%;
            z-index: 9999;
            transition: width 0.3s ease;
            box-shadow: 0 0 10px var(--neon-cyan);
        }
        
        .loading-bar.active {
            animation: loadingProgress 1s ease-in-out;
        }
        
        @keyframes loadingProgress {
            0% { width: 0%; }
            20% { width: 30%; }
            50% { width: 60%; }
            80% { width: 85%; }
            100% { width: 95%; }
        }
        
        .loading-bar.complete {
            width: 100%;
            opacity: 0;
            transition: width 0.2s ease, opacity 0.3s ease 0.2s;
        }
        
        /* Page transition */
        .page-content {
            transition: opacity 0.15s ease;
        }
        
        .page-content.loading {
            opacity: 0.5;
            pointer-events: none;
        }
        
        .page-content.fade-in {
            animation: fadeIn 0.2s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Skeleton loading placeholders */
        .skeleton {
            background: linear-gradient(90deg, 
                rgba(255,255,255,0.05) 25%, 
                rgba(255,255,255,0.1) 50%, 
                rgba(255,255,255,0.05) 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 8px;
        }
        
        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
    
    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-bottom-nav">
        <a href="<?= base_url('admin/dashboard') ?>" class="nav-item <?= uri_string() === 'admin/dashboard' || uri_string() === '' ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="<?= base_url('admin/billing/customers') ?>" class="nav-item <?= strpos(uri_string(), 'billing/customers') !== false ? 'active' : '' ?>">
            <i class="fas fa-users"></i>
            <span>Pelanggan</span>
        </a>
        <?php if (session()->get('admin_role') === 'technician'): ?>
        <a href="<?= base_url('admin/trouble') ?>" class="nav-item <?= strpos(uri_string(), 'admin/trouble') !== false ? 'active' : '' ?>">
            <i class="fas fa-tools"></i>
            <span>Tiket</span>
        </a>
        <?php else: ?>
        <a href="<?= base_url('admin/billing/invoices') ?>" class="nav-item <?= strpos(uri_string(), 'billing/invoices') !== false ? 'active' : '' ?>">
            <i class="fas fa-file-invoice-dollar"></i>
            <span>Tagihan</span>
        </a>
        <?php endif; ?>
        <a href="<?= base_url('admin/map') ?>" class="nav-item <?= strpos(uri_string(), 'map') !== false ? 'active' : '' ?>">
            <i class="fas fa-map-marked-alt"></i>
            <span>Map</span>
        </a>
        <div class="nav-item" onclick="toggleSidebarMobile()">
            <i class="fas fa-bars"></i>
            <span>Menu</span>
        </div>
    </nav>
    
    <style>
        /* Bottom Navigation Styles */
        .mobile-bottom-nav {
            display: none; /* Default hidden on desktop */
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(20, 20, 35, 0.95); /* Matches var(--bg-card) mostly */
            backdrop-filter: blur(10px);
            border-top: 1px solid var(--border-color);
            height: 60px;
            z-index: 1060;
            padding-bottom: env(safe-area-inset-bottom);
        }
        
        .mobile-bottom-nav .nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.7rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .mobile-bottom-nav .nav-item i {
            font-size: 1.25rem;
            margin-bottom: 2px;
        }
        
        .mobile-bottom-nav .nav-item.active {
            color: var(--neon-cyan);
            border-top: 2px solid var(--neon-cyan);
        }
        
        .mobile-bottom-nav .nav-item:active {
            background: rgba(255, 255, 255, 0.05);
        }
        
        @media (max-width: 768px) {
            .mobile-bottom-nav {
                display: flex;
            }
            
            /* Add padding to body so content isn't covered by nav */
            .page-content {
                padding-bottom: 70px !important;
            }
            
            /* Hide hamburger on top header since we have bottom menu now? 
               Optional: I'll keep top toggle as well for flexibility, 
               or you can hide it with: .top-header .mobile-toggle { display: none; } */
        }
    </style>
    
    <script>
        // ==============================================
        // TURBO NAVIGATION SYSTEM - OPTIMIZED
        // ==============================================
        
        const TurboNav = {
            cache: new Map(),
            prefetchQueue: new Set(),
            isNavigating: false,
            prefetchedLinks: new Set(),
            
            init() {
                // Intercept all internal link clicks
                document.addEventListener('click', this.handleClick.bind(this));
                
                // Handle browser back/forward
                window.addEventListener('popstate', this.handlePopState.bind(this));
                
                // Prefetch on hover (desktop) - immediate
                document.addEventListener('mouseover', this.handleHover.bind(this));
                
                // Prefetch on touch start (mobile)
                document.addEventListener('touchstart', this.handleHover.bind(this), { passive: true });
                
                // Aggressive prefetch: prefetch all sidebar menu links on page load
                this.prefetchSidebarLinks();
            },
            
            // Prefetch all sidebar menu links for instant navigation
            prefetchSidebarLinks() {
                setTimeout(() => {
                    const sidebarLinks = document.querySelectorAll('.sidebar-menu a[href], .menu-submenu a[href]');
                    sidebarLinks.forEach(link => {
                        const href = link.getAttribute('href');
                        if (this.isInternalLink(href) && !this.prefetchedLinks.has(href)) {
                            this.prefetchedLinks.add(href);
                            this.prefetch(href);
                        }
                    });
                }, 500); // Start prefetching 500ms after page load
            },
            
            handleClick(e) {
                // Handle bottom nav clicks specifically or general links
                const link = e.target.closest('a[href]') || e.target.closest('.nav-item a');
                
                if (!link) return;
                
                const href = link.getAttribute('href');
                
                // Skip if: external link, has target, is download, modifier key pressed, or data-turbo=false
                if (!this.isInternalLink(href) || 
                    link.target === '_blank' || 
                    link.hasAttribute('download') ||
                    link.dataset.turbo === 'false' ||
                    e.ctrlKey || e.metaKey || e.shiftKey) {
                    return;
                }
                
                // Skip logout link
                if (href.includes('logout')) return;
                
                e.preventDefault();
                
                // Instant visual feedback
                link.style.opacity = '0.6';
                setTimeout(() => link.style.opacity = '', 150);
                
                // CLOSE SIDEBAR ON MOBILE AFTER CLICK
                if (window.innerWidth <= 768 && document.getElementById('sidebar').classList.contains('mobile-open')) {
                    toggleSidebarMobile();
                }
                
                this.navigate(href);
            },
            
            handlePopState(e) {
                if (e.state && e.state.turbo) {
                    this.navigate(window.location.href, false);
                }
            },
            
            handleHover(e) {
                const link = e.target.closest('a[href]');
                if (!link) return;
                
                const href = link.getAttribute('href');
                if (this.isInternalLink(href) && !this.cache.has(href) && !this.prefetchQueue.has(href)) {
                    this.prefetch(href);
                }
            },
            
            isInternalLink(href) {
                if (!href || href.startsWith('#') || href.startsWith('javascript:')) return false;
                if (href.startsWith('http')) {
                    return href.includes(window.location.host);
                }
                return true;
            },
            
            async prefetch(url) {
                if (this.prefetchQueue.has(url) || this.cache.has(url)) return;
                
                this.prefetchQueue.add(url);
                try {
                    const response = await fetch(url, { 
                        method: 'GET',
                        headers: { 'X-Requested-With': 'TurboNav' },
                        priority: 'low'
                    });
                    if (response.ok) {
                        const html = await response.text();
                        this.cache.set(url, { html, timestamp: Date.now() });
                    }
                } catch (e) {
                    // Silently fail prefetch
                }
                this.prefetchQueue.delete(url);
            },
            
            async navigate(url, pushState = true) {
                if (this.isNavigating) return;
                this.isNavigating = true;
                
                const loadingBar = document.getElementById('loadingBar');
                const pageContent = document.getElementById('pageContent');
                
                // Start loading animation - faster
                loadingBar.classList.remove('complete');
                loadingBar.classList.add('active');
                loadingBar.style.width = '50%';
                pageContent.style.opacity = '0.7';
                
                try {
                    let html;
                    
                    // Check cache first (10 min expiry for speed)
                    const cached = this.cache.get(url);
                    if (cached && (Date.now() - cached.timestamp) < 600000) {
                        html = cached.html;
                        loadingBar.style.width = '95%';
                    } else {
                        const response = await fetch(url, {
                            method: 'GET',
                            headers: { 'X-Requested-With': 'TurboNav' }
                        });
                        
                        if (!response.ok) throw new Error('Network error');
                        
                        html = await response.text();
                        this.cache.set(url, { html, timestamp: Date.now() });
                        loadingBar.style.width = '95%';
                    }
                    
                    // Parse and update content
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    // Update page title
                    const newTitle = doc.querySelector('title');
                    if (newTitle) document.title = newTitle.textContent;
                    
                    // Update main content
                    const newContent = doc.querySelector('.page-content');
                    if (newContent) {
                        pageContent.innerHTML = newContent.innerHTML;
                        pageContent.style.opacity = '1';
                        pageContent.classList.add('fade-in');
                        setTimeout(() => pageContent.classList.remove('fade-in'), 100);
                    }
                    
                    // Update page title in header
                    const newPageTitle = doc.querySelector('.page-title');
                    const currentPageTitle = document.querySelector('.page-title');
                    if (newPageTitle && currentPageTitle) {
                        currentPageTitle.textContent = newPageTitle.textContent;
                    }
                    
                    // Update active menu
                    this.updateActiveMenu(url);
                    
                    // Execute inline scripts in new content
                    const scripts = pageContent.querySelectorAll('script');
                    scripts.forEach(script => {
                        const newScript = document.createElement('script');
                        if (script.src) {
                            newScript.src = script.src;
                        } else {
                            newScript.textContent = script.textContent;
                        }
                        document.body.appendChild(newScript);
                        newScript.remove();
                    });
                    
                    // Push to history
                    if (pushState) {
                        history.pushState({ turbo: true }, '', url);
                    }
                    
                    // Scroll to top
                    document.querySelector('.page-content').scrollTop = 0;
                    
                } catch (error) {
                    console.error('Navigation error:', error);
                    // Fallback to regular navigation
                    window.location.href = url;
                    return;
                }
                
                // Complete loading animation
                loadingBar.style.width = '100%';
                setTimeout(() => {
                    loadingBar.classList.remove('active');
                    loadingBar.classList.add('complete');
                    loadingBar.style.width = '0%';
                }, 200);
                
                this.isNavigating = false;
            },
            
            updateActiveMenu(url) {
                // Remove current active states
                document.querySelectorAll('.menu-item.active, .submenu-item.active, .nav-item.active').forEach(el => {
                    el.classList.remove('active');
                });
                
                // Find and activate matching menu item
                const path = new URL(url, window.location.origin).pathname;
                
                // Update Sidebar
                document.querySelectorAll('.menu-item, .submenu-item').forEach(link => {
                    const href = link.getAttribute('href');
                    if (href && path.endsWith(href.replace(/^.*\/public/, '').replace(/^\//, ''))) {
                        link.classList.add('active');
                        // Open parent submenu if needed
                        const parent = link.closest('.menu-submenu');
                        if (parent) {
                            parent.classList.add('active');
                            const parentToggle = parent.previousElementSibling;
                            if (parentToggle) parentToggle.classList.add('open');
                        }
                    }
                });
                
                // Update Bottom Nav
                document.querySelectorAll('.mobile-bottom-nav .nav-item').forEach(link => {
                     const href = link.getAttribute('href');
                     if (href && path.includes(href)) { // Use simple includes for bottom nav
                         link.classList.add('active');
                     }
                });
            }
        };
        
        // Initialize when DOM is ready
        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', () => {
            TurboNav.init();
            updateThemeIcon();
        });
        
        // ==============================================
        // EXISTING FUNCTIONS
        // ==============================================

        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon();
        }
        
        function updateThemeIcon() {
            const theme = document.documentElement.getAttribute('data-theme') || 'dark';
            const icon = document.querySelector('.theme-icon');
            if (icon) {
                icon.className = theme === 'light' ? 'fas fa-sun theme-icon' : 'fas fa-moon theme-icon';
            }
        }
        
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        }
        
        function toggleSidebarMobile() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('show');
        }
        
        function toggleSubmenu(element) {
            element.classList.toggle('open');
            const submenu = element.nextElementSibling;
            if (submenu && submenu.classList.contains('menu-submenu')) {
                submenu.classList.toggle('active');
            }
        }
        
        function toggleUserMenu() {
            const menu = document.getElementById('userMenu');
            if (menu) {
                menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
            }
        }
        
        // Close user menu when clicking outside
        document.addEventListener('click', function(e) {
            const userDropdown = document.querySelector('.user-dropdown');
            const userMenu = document.getElementById('userMenu');
            if (userMenu && userDropdown && !userDropdown.contains(e.target)) {
                userMenu.style.display = 'none';
            }
        });
        
        // Restore sidebar state
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            document.getElementById('sidebar').classList.add('collapsed');
        }
    </script>
    
    <?= $this->renderSection('scripts') ?>
    <!-- DataTables JS & Auto-Init -->
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" type="text/javascript"></script>
    <style>
        /* Dark Mode DataTables Overrides */
        .datatable-wrapper .datatable-top, 
        .datatable-wrapper .datatable-bottom {
            color: var(--text-secondary);
        }
        .datatable-selector, .datatable-input {
            background: rgba(255,255,255,0.05) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-primary) !important;
            border-radius: 6px;
        }
        .datatable-pagination a {
            color: var(--text-muted);
        }
        .datatable-pagination .datatable-active a {
            background-color: var(--neon-cyan);
            color: #000;
            cursor: default;
        }
        .datatable-sorter::after { border-bottom-color: var(--text-primary) !important; }
        .datatable-sorter::before { border-top-color: var(--text-primary) !important; }
        .datatable-table { border-bottom: 0 !important; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tables = document.querySelectorAll('.data-table');
            tables.forEach(table => {
                new simpleDatatables.DataTable(table, {
                    perPage: 10,
                    perPageSelect: [10, 25, 50, 100],
                    labels: {
                        placeholder: "Cari data...",
                        perPage: "Data per halaman",
                        noRows: "Tidak ada data ditemukan",
                        info: "Menampilkan {start} sampai {end} dari {rows} data",
                    }
                });
            });
        });
    </script>
</body>
</html>
