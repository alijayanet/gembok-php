<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a0a0f">
    <title>Edit WiFi - Portal Pelanggan</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-primary: #0a0a0f;
            --bg-card: rgba(20, 20, 35, 0.95);
            --neon-cyan: #00f5ff;
            --neon-purple: #bf00ff;
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);
            --text-muted: rgba(255, 255, 255, 0.5);
            --border-color: rgba(255, 255, 255, 0.1);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            padding-bottom: 70px;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 30%, rgba(0, 245, 255, 0.05) 0%, transparent 50%);
            z-index: -1;
        }
        
        .header {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-icon {
            width: 40px;
            height: 40px;
            background: var(--neon-cyan);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: var(--bg-primary);
            margin: 0 auto 0.5rem;
        }
        
        .content {
            padding: 1rem;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .card {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1rem;
        }
        
        h2 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--neon-cyan);
            text-align: center;
        }
        
        .customer-name {
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .form-group input {
            width: 100%;
            padding: 0.875rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.2s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--neon-cyan);
            background: rgba(255, 255, 255, 0.08);
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9375rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
            margin-top: 0.75rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--neon-cyan) 0%, var(--neon-purple) 100%);
            color: var(--bg-primary);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }
        
        .btn:active {
            transform: scale(0.98);
        }
        
        @media (min-width: 768px) {
            .content { padding: 2rem; }
            .header { padding: 1.5rem 2rem; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-icon">
            <i class="fas fa-wifi"></i>
        </div>
        <h2 style="text-align: center; font-size: 1.125rem; color: var(--text-primary);">Edit WiFi</h2>
    </header>
    
    <div class="content">
        <div class="card">
            <div class="customer-name">
                Pelanggan: <strong><?= esc($customer['name'] ?? 'Pelanggan') ?></strong>
            </div>
            
            <form method="post" action="<?= base_url('portal/wifi?phone=' . urlencode($customer['phone'] ?? '')) ?>">
                <div class="form-group">
                    <label for="ssid">
                        <i class="fas fa-signal"></i> Nama WiFi (SSID)
                    </label>
                    <input type="text" id="ssid" name="ssid" value="<?= esc($current['ssid'] ?? '') ?>" placeholder="Contoh: WiFi-Rumah" required>
                </div>
                
                <div class="form-group">
                    <label for="wifi_password">
                        <i class="fas fa-lock"></i> Password WiFi
                    </label>
                    <input type="password" id="wifi_password" name="wifi_password" placeholder="Minimal 8 karakter" minlength="8" required>
                    <small style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 0.25rem;">
                        * Password minimal 8 karakter untuk keamanan
                    </small>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
                <a href="<?= base_url('portal/dashboard') ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Batal
                </a>
            </form>
        </div>
    </div>

    <?= $this->include('portal/_mobile_nav') ?>
</body>
</html>
