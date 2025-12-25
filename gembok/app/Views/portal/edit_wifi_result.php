<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a0a0f">
    <title>Hasil Edit WiFi - Portal Pelanggan</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-primary: #0a0a0f;
            --bg-card: rgba(20, 20, 35, 0.95);
            --neon-cyan: #00f5ff;
            --neon-purple: #bf00ff;
            --neon-green: #00ff88;
            --neon-red: #ff4757;
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);
            --border-color: rgba(255, 255, 255, 0.1);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            padding-bottom: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
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
        
        .content {
            padding: 1rem;
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
        }
        
        .card {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 2rem 1.5rem;
            text-align: center;
        }
        
        h2 {
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            color: var(--neon-cyan);
        }
        
        .result-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
        }
        
        .result-icon.success {
            background: rgba(0, 255, 136, 0.1);
            color: var(--neon-green);
            border: 2px solid var(--neon-green);
        }
        
        .result-icon.error {
            background: rgba(255, 71, 87, 0.1);
            color: var(--neon-red);
            border: 2px solid var(--neon-red);
        }
        
        .result-message {
            font-size: 1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .result-message.success { color: var(--neon-green); }
        .result-message.error { color: var(--neon-red); }
        
        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, var(--neon-cyan) 0%, var(--neon-purple) 100%);
            color: var(--bg-primary);
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9375rem;
            transition: all 0.3s;
        }
        
        .btn:active {
            transform: scale(0.98);
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="card">
            <div class="result-icon <?= strpos($message ?? '', '✅') !== false ? 'success' : 'error' ?>">
                <i class="fas <?= strpos($message ?? '', '✅') !== false ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
            </div>
            
            <h2>Hasil Update WiFi</h2>
            
            <div class="result-message <?= strpos($message ?? '', '✅') !== false ? 'success' : 'error' ?>">
                <?= esc($message ?? 'Proses selesai') ?>
            </div>
            
            <a href="<?= base_url('portal/dashboard') ?>" class="btn">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>

    <?= $this->include('portal/_mobile_nav') ?>
</body>
</html>
