<!-- Mobile Bottom Navigation for Portal -->
<nav class="portal-mobile-bottom-nav">
    <a href="<?= base_url('portal/dashboard') ?>" class="nav-item <?= uri_string() === 'portal/dashboard' || uri_string() === 'portal' ? 'active' : '' ?>">
        <i class="fas fa-home"></i>
        <span>Home</span>
    </a>
    <a href="<?= base_url('portal/invoices') ?>" class="nav-item <?= strpos(uri_string(), 'portal/invoices') !== false ? 'active' : '' ?>">
        <i class="fas fa-file-invoice-dollar"></i>
        <span>Tagihan</span>
    </a>
    <a href="<?= base_url('portal/dashboard') ?>" class="nav-item" onclick="scrollToWifiSection(event)">
        <i class="fas fa-wifi"></i>
        <span>WiFi</span>
    </a>
    <a href="<?= base_url('portal/dashboard') ?>" class="nav-item" onclick="scrollToAccountSection(event)">
        <i class="fas fa-user-cog"></i>
        <span>Akun</span>
    </a>
</nav>

<!-- Theme Toggle Button (Fixed Top Right) -->
<button class="portal-theme-toggle" onclick="togglePortalTheme()" title="Ganti Tema">
    <i class="fas fa-moon theme-icon"></i>
</button>

<style>
    /* Light Theme Variables */
    [data-theme="light"] {
        --bg-primary: #f4f6f8;
        --bg-secondary: #ffffff;
        --bg-card: rgba(255, 255, 255, 0.95);
        
        --neon-cyan: #0891b2;
        --neon-purple: #7c3aed;
        --neon-pink: #db2777;
        --neon-green: #059669;
        --neon-yellow: #d97706;
        --neon-red: #dc2626;
        
        --text-primary: #1a1a1a;
        --text-secondary: rgba(0, 0, 0, 0.7);
        --text-muted: rgba(0, 0, 0, 0.5);
        
        --border-color: rgba(0, 0, 0, 0.1);
    }

    /* Light theme specific adjustments */
    [data-theme="light"] body::before {
        background: radial-gradient(circle at 30% 30%, rgba(8, 145, 178, 0.08) 0%, transparent 50%),
                    radial-gradient(circle at 70% 70%, rgba(124, 58, 237, 0.08) 0%, transparent 50%);
    }
    
    [data-theme="light"] .header {
        background: rgba(255, 255, 255, 0.98);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    [data-theme="light"] .card {
        background: rgba(255, 255, 255, 0.98);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }
    
    [data-theme="light"] .form-group input {
        background: rgba(0, 0, 0, 0.03);
        border-color: rgba(0, 0, 0, 0.15);
    }
    
    [data-theme="light"] .form-group input:focus {
        background: rgba(0, 0, 0, 0.05);
    }
    
    [data-theme="light"] .invoice-item,
    [data-theme="light"] .status-item {
        background: rgba(0, 0, 0, 0.02);
    }
    
    [data-theme="light"] .portal-theme-toggle {
        background: rgba(0, 0, 0, 0.05);
        color: #1a1a1a;
    }
    
    [data-theme="light"] .portal-mobile-bottom-nav {
        background: rgba(255, 255, 255, 0.98);
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
    }

    /* Theme Toggle Button */
    .portal-theme-toggle {
        position: fixed;
        top: 1rem;
        left: 1rem;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 999;
        transition: all 0.3s;
        backdrop-filter: blur(10px);
    }
    
    .portal-theme-toggle:active {
        transform: scale(0.95);
    }
    
    .portal-theme-toggle .theme-icon {
        font-size: 1.25rem;
        transition: transform 0.3s;
    }
    
    .portal-theme-toggle:hover .theme-icon {
        transform: rotate(20deg);
    }

    /* Mobile Bottom Navigation for Portal */
    .portal-mobile-bottom-nav {
        display: none;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(20, 20, 35, 0.98);
        backdrop-filter: blur(15px);
        border-top: 1px solid var(--border-color);
        padding: 0.5rem 0;
        padding-bottom: calc(0.5rem + env(safe-area-inset-bottom));
        z-index: 1000;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.3);
    }
    
    @media (max-width: 768px) {
        .portal-mobile-bottom-nav {
            display: flex;
            justify-content: space-around;
            align-items: center;
        }
        
        /* Add padding to body to prevent content being hidden by bottom nav */
        body {
            padding-bottom: 70px !important;
        }
        
        .page-content {
            padding-bottom: 70px !important;
        }
    }
    
    .portal-mobile-bottom-nav .nav-item {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 0.5rem;
        text-decoration: none;
        color: rgba(255, 255, 255, 0.5);
        transition: all 0.2s;
        font-size: 0.65rem;
        position: relative;
    }
    
    .portal-mobile-bottom-nav .nav-item i {
        font-size: 1.25rem;
        margin-bottom: 0.25rem;
    }
    
    .portal-mobile-bottom-nav .nav-item.active {
        color: #00f5ff;
    }
    
    .portal-mobile-bottom-nav .nav-item.active::before {
        content: '';
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 30px;
        height: 3px;
        background: #00f5ff;
        border-radius: 0 0 3px 3px;
        box-shadow: 0 0 10px #00f5ff;
    }
    
    .portal-mobile-bottom-nav .nav-item:active {
        background: rgba(255, 255, 255, 0.05);
    }
    
    [data-theme="light"] .portal-mobile-bottom-nav .nav-item {
        color: rgba(0, 0, 0, 0.5);
    }
    
    [data-theme="light"] .portal-mobile-bottom-nav .nav-item.active {
        color: #0891b2;
    }
    
    [data-theme="light"] .portal-mobile-bottom-nav .nav-item.active::before {
        background: #0891b2;
        box-shadow: 0 0 10px #0891b2;
    }
</style>

<script>
    // Initialize theme from localStorage on page load
    (function() {
        const savedTheme = localStorage.getItem('portalTheme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
        updatePortalThemeIcon();
    })();
    
    // Toggle theme function
    function togglePortalTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('portalTheme', newTheme);
        updatePortalThemeIcon();
    }
    
    // Update theme icon
    function updatePortalThemeIcon() {
        const theme = document.documentElement.getAttribute('data-theme') || 'dark';
        const icon = document.querySelector('.portal-theme-toggle .theme-icon');
        if (icon) {
            icon.className = theme === 'light' ? 'fas fa-sun theme-icon' : 'fas fa-moon theme-icon';
        }
    }
    
    // Ensure icon is updated when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updatePortalThemeIcon);
    } else {
        updatePortalThemeIcon();
    }
    
    // Scroll to WiFi section function
    function scrollToWifiSection(event) {
        // Check if we're already on dashboard page
        const currentPath = window.location.pathname;
        
        if (currentPath.includes('portal/dashboard') || currentPath === '/portal' || currentPath.endsWith('portal')) {
            // Already on dashboard, just scroll
            event.preventDefault();
            
            // Find WiFi Settings section
            const wifiSection = document.querySelector('#wifi-section') || 
                              document.querySelector('[id*="wifi"]');
            
            if (wifiSection) {
                wifiSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            
            // Update active state
            document.querySelectorAll('.portal-mobile-bottom-nav .nav-item').forEach(item => {
                item.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
        } else {
            // Not on dashboard, redirect to dashboard with hash
            window.location.href = event.currentTarget.getAttribute('href') + '#wifi';
        }
    }
    
    // Scroll to Account section function
    function scrollToAccountSection(event) {
        // Check if we're already on dashboard page
        const currentPath = window.location.pathname;
        
        if (currentPath.includes('portal/dashboard') || currentPath === '/portal' || currentPath.endsWith('portal')) {
            // Already on dashboard, just scroll
            event.preventDefault();
            
            // Find Account Settings section
            const accountSection = document.querySelector('#account-section') || 
                                  document.querySelector('[id*="account"]');
            
            if (accountSection) {
                accountSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            
            // Update active state
            document.querySelectorAll('.portal-mobile-bottom-nav .nav-item').forEach(item => {
                item.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
        } else {
            // Not on dashboard, redirect to dashboard with hash
            window.location.href = event.currentTarget.getAttribute('href') + '#account';
        }
    }
    
    // Auto-scroll to section if hash is present in URL
    window.addEventListener('DOMContentLoaded', function() {
        const hash = window.location.hash;
        if (hash === '#wifi' || hash === '#wifi-section') {
            setTimeout(function() {
                const wifiSection = document.querySelector('#wifi-section');
                if (wifiSection) {
                    wifiSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 300); // Small delay to ensure page is fully loaded
        } else if (hash === '#account' || hash === '#account-section') {
            setTimeout(function() {
                const accountSection = document.querySelector('#account-section');
                if (accountSection) {
                    accountSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 300);
        }
    });
</script>

