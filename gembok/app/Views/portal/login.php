<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a0a0f">
    <title>Portal Pelanggan - Gembok</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-primary: #0a0a0f;
            --bg-secondary: #12121a;
            --bg-card: rgba(20, 20, 35, 0.9);
            --neon-cyan: #00f5ff;
            --neon-purple: #bf00ff;
            --neon-pink: #ff00aa;
            --neon-green: #00ff88;
            --gradient-primary: linear-gradient(135deg, #00f5ff 0%, #bf00ff 100%);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.6);
            --text-muted: rgba(255, 255, 255, 0.4);
            --border-color: rgba(255, 255, 255, 0.08);
            --border-glow: rgba(0, 245, 255, 0.3);
            --shadow-neon: 0 0 30px rgba(0, 245, 255, 0.3);
        }
        
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
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 30%, rgba(0, 245, 255, 0.05) 0%, transparent 50%),
                        radial-gradient(circle at 70% 70%, rgba(191, 0, 255, 0.05) 0%, transparent 50%);
            animation: backgroundMove 20s ease-in-out infinite;
            z-index: -1;
        }
        
        @keyframes backgroundMove {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-5%, -5%) rotate(5deg); }
        }
        
        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 1rem;
        }
        
        .login-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 2.5rem;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo {
            width: 70px;
            height: 70px;
            background: var(--gradient-primary);
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            color: var(--bg-primary);
            margin-bottom: 1rem;
            box-shadow: 0 8px 32px rgba(0, 245, 255, 0.3);
        }
        
        .login-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .login-subtitle {
            color: var(--text-secondary);
            font-size: 0.9375rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--neon-cyan);
            font-size: 1.125rem;
        }
        
        .form-control {
            width: 100%;
            padding: 1rem;
            padding-left: 3rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--neon-cyan);
            background: rgba(255, 255, 255, 0.05);
            box-shadow: 0 0 0 3px rgba(0, 245, 255, 0.1);
        }
        
        .info-text {
            background: rgba(0, 245, 255, 0.05);
            border: 1px solid var(--neon-cyan);
            border-radius: 8px;
            padding: 0.75rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }
        
        .info-text i {
            color: var(--neon-cyan);
            margin-top: 0.125rem;
        }
        
        .btn-login {
            width: 100%;
            padding: 1rem;
            background: var(--gradient-primary);
            border: none;
            border-radius: 12px;
            color: var(--bg-primary);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 8px 24px rgba(0, 245, 255, 0.3);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(0, 245, 255, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 0.9375rem;
        }
        
        .alert-error {
            background: rgba(255, 71, 87, 0.1);
            border: 1px solid var(--neon-pink);
            color: var(--neon-pink);
        }
        
        .alert-success {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid var(--neon-green);
            color: var(--neon-green);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
            color: var(--text-muted);
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-lock"></i>
                </div>
                <h1 class="login-title">Portal Pelanggan</h1>
                <p class="login-subtitle">Masuk untuk mengakses dashboard Anda</p>
            </div>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= esc($error) ?>
            </div>
            <?php endif; ?>
            
            <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= session()->getFlashdata('error') ?>
            </div>
            <?php endif; ?>
            
            <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= session()->getFlashdata('success') ?>
            </div>
            <?php endif; ?>
            
            <form action="<?= base_url('login') ?>" method="POST">
                <div class="form-group">
                    <label class="form-label">ID Pelanggan (PPPoE / No. HP)</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="id_pelanggan" class="form-control" placeholder="Masukkan ID Pelanggan" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" class="form-control" placeholder="Masukkan Password" required>
                    </div>
                </div>
                
                <p class="info-text">
                    <i class="fas fa-info-circle"></i>
                    Untuk pertama kali login, gunakan password default: <strong>1234</strong>
                </p>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Masuk Portal
                </button>
            </form>
            
            <div class="login-footer">
                <p>&copy; <?= date('Y') ?> Gembok ISP Management</p>
            </div>
        </div>
    </div>
    
    <!-- Theme Toggle Button for Login Page Only -->
    <div id="theme-toggle" style="position: fixed; top: 1rem; right: 1rem; width: 50px; height: 50px; background: rgba(255,255,255,0.1); border: 2px solid rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 9999; backdrop-filter: blur(10px); box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
        <i class="fas fa-moon" id="theme-icon" style="font-size: 1.25rem; color: #fff;"></i>
    </div>
    
    <script>
        // Theme toggle for login page
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        const html = document.documentElement;
        
        // Load saved theme
        const savedTheme = localStorage.getItem('portalTheme') || 'dark';
        html.setAttribute('data-theme', savedTheme);
        updateIcon();
        
        // Toggle on click
        themeToggle.addEventListener('click', function() {
            const current = html.getAttribute('data-theme') || 'dark';
            const newTheme = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('portalTheme', newTheme);
            updateIcon();
        });
        
        function updateIcon() {
            const theme = html.getAttribute('data-theme') || 'dark';
            themeIcon.className = theme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
        }
    </script>
    
    <style>
        /* Light theme for login */
        [data-theme="light"] {
            --bg-primary: #f4f6f8;
            --bg-card: rgba(255, 255, 255, 0.95);
            --text-primary: #1a1a1a;
            --text-secondary: rgba(0, 0, 0, 0.7);
            --text-muted: rgba(0, 0, 0, 0.5);
            --border-color: rgba(0, 0, 0, 0.1);
        }
        
        [data-theme="light"] body::before {
            background: radial-gradient(circle at 30% 30%, rgba(8, 145, 178, 0.08) 0%, transparent 50%),
                        radial-gradient(circle at 70% 70%, rgba(124, 58, 237, 0.08) 0%, transparent 50%);
        }
        
        [data-theme="light"] .login-card {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }
        
        [data-theme="light"] .form-control {
            background: rgba(0, 0, 0, 0.03);
            border-color: rgba(0, 0, 0, 0.15);
        }
        
        [data-theme="light"] #theme-toggle {
            background: rgba(0, 0, 0, 0.05) !important;
            border-color: rgba(0, 0, 0, 0.15) !important;
        }
        
        [data-theme="light"] #theme-icon {
            color: #1a1a1a !important;
        }
    </style>
</body>
</html>
