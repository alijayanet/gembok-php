<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a0a0f">
    <title>Tagihan - Portal Pelanggan</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-primary: #0a0a0f;
            --bg-secondary: #12121a;
            --bg-card: rgba(20, 20, 35, 0.95);
            --neon-cyan: #00f5ff;
            --neon-purple: #bf00ff;
            --neon-pink: #ff00aa;
            --neon-green: #00ff88;
            --neon-yellow: #ffeb3b;
            --neon-red: #ff4757;
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);
            --text-muted: rgba(255, 255, 255, 0.5);
            --border-color: rgba(255, 255, 255, 0.1);
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
            padding-bottom: 70px;
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
        
        .header {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
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
        }
        
        .content {
            padding: 1rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .card {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        h2 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: var(--neon-cyan);
        }
        
        .invoice-list {
            margin-top: 1rem;
        }
        
        .invoice-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            transition: all 0.2s;
        }
        
        .invoice-item:active {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .invoice-month {
            font-weight: 600;
            font-size: 0.9375rem;
        }
        
        .invoice-amount {
            font-weight: 700;
            font-size: 1rem;
            color: var(--neon-cyan);
        }
        
        .invoice-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8125rem;
            color: var(--text-secondary);
        }
        
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-success {
            background: rgba(0, 255, 136, 0.2);
            color: var(--neon-green);
            border: 1px solid var(--neon-green);
        }
        
        .badge-warning {
            background: rgba(255, 235, 59, 0.2);
            color: var(--neon-yellow);
            border: 1px solid var(--neon-yellow);
        }
        
        .btn {
            display: inline-block;
            padding: 1rem;
            background: linear-gradient(135deg, var(--neon-cyan) 0%, var(--neon-purple) 100%);
            border: none;
            border-radius: 8px;
            color: var(--bg-primary);
            font-size: 0.9375rem;
            font-weight: 700;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            margin-top: 1rem;
        }
        
        .btn:active {
            transform: scale(0.98);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-muted);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        
        @media (min-width: 768px) {
            .content {
                padding: 2rem;
            }
            
            .header {
                padding: 1.5rem 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-title">
            <div class="header-icon">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div>
                <h1 style="font-size: 1.125rem; font-weight: 700;">Daftar Tagihan</h1>
                <p style="font-size: 0.8125rem; color: var(--text-secondary); margin-top: 0.125rem;">
                    <?= esc($customer['name'] ?? 'Pelanggan') ?>
                </p>
            </div>
        </div>
    </header>
    
    <div class="content">
        <?php if (empty($invoices)): ?>
            <div class="card">
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Belum ada tagihan yang tersedia</p>
                </div>
            </div>
        <?php else: ?>
            <div class="invoice-list">
                <?php foreach ($invoices as $inv): ?>
                <div class="invoice-item">
                    <div class="invoice-header">
                        <div class="invoice-month">
                            <?= esc($inv['description'] ?? '-') ?>
                        </div>
                        <div class="invoice-amount">
                            Rp <?= number_format($inv['amount'] ?? 0, 0, ',', '.') ?>
                        </div>
                    </div>
                    <div class="invoice-footer">
                        <span>
                            <i class="far fa-calendar"></i> 
                            <?= date('d M Y', strtotime($inv['due_date'] ?? 'now')) ?>
                        </span>
                        <span class="badge <?= ($inv['status'] ?? '') === 'paid' ? 'badge-success' : 'badge-warning' ?>">
                            <?= ($inv['status'] ?? '') === 'paid' ? '✓ Lunas' : '⏳ Belum Dibayar' ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <a href="<?= base_url('portal/dashboard') ?>" class="btn">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>

    <?= $this->include('portal/_mobile_nav') ?>
</body>
</html>
