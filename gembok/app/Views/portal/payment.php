<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a0a0f">
    <title>Pilih Pembayaran - Portal Pelanggan</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .back-btn {
            color: var(--neon-cyan);
            text-decoration: none;
            font-size: 1.25rem;
            transition: transform 0.2s;
        }
        
        .back-btn:active { transform: scale(0.9); }
        
        .content {
            padding: 1rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .invoice-card {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .amount-label {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }
        
        .amount-value {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--neon-cyan), var(--neon-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .invoice-number {
            display: inline-block;
            background: rgba(0, 245, 255, 0.15);
            color: var(--neon-cyan);
            border: 1px solid var(--neon-cyan);
            padding: 0.25rem 0.75rem;
            border-radius: 99px;
            font-size: 0.825rem;
            font-weight: 600;
            margin-top: 0.75rem;
        }
        
        .channel-group {
            margin-bottom: 1.5rem;
        }
        
        .channel-group-title {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--neon-cyan);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
        }
        
        .channel-item {
            display: flex;
            align-items: center;
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 0.75rem;
            position: relative;
        }
        
        .channel-item:active {
            transform: scale(0.98);
        }
        
        .channel-item.selected {
            border-color: var(--neon-cyan);
            background: rgba(0, 245, 255, 0.05);
        }
        
        .channel-item input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .channel-logo {
            width: 50px;
            height: 30px;
            object-fit: contain;
            margin-right: 1rem;
            background: white;
            padding: 0.25rem;
            border-radius: 4px;
        }
        
        .channel-info {
            flex: 1;
        }
        
        .channel-name {
            font-weight: 600;
            font-size: 0.9375rem;
            display: block;
            margin-bottom: 0.25rem;
        }
        
        .channel-fee {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        
        .check-icon {
            color: var(--neon-cyan);
            font-size: 1.25rem;
            opacity: 0;
            transform: scale(0.5);
            transition: all 0.2s;
        }
        
        .channel-item.selected .check-icon {
            opacity: 1;
            transform: scale(1);
        }
        
        .tos-box {
            margin: 1.5rem 0;
            padding: 1rem;
            background: rgba(255, 235, 59, 0.1);
            border-radius: 8px;
            border: 1px solid var(--neon-yellow);
        }
        
        .tos-box label {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            cursor: pointer;
            font-size: 0.875rem;
        }
        
        .tos-box a {
            color: var(--neon-cyan);
            text-decoration: underline;
        }
        
        .btn-pay {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--neon-cyan), var(--neon-purple));
            border: none;
            border-radius: 8px;
            color: var(--bg-primary);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1.5rem;
        }
        
        .btn-pay:disabled {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-muted);
            cursor: not-allowed;
        }
        
        .btn-pay:active:not(:disabled) {
            transform: scale(0.98);
        }
        
        .alert {
            background: rgba(255, 71, 87, 0.1);
            border: 1px solid var(--neon-red);
            color: var(--neon-red);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
        
        .secure-badge {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.75rem;
            margin-top: 1rem;
        }
        
        /* Override theme toggle position for payment page - move to right */
        .portal-theme-toggle {
            left: auto !important;
            right: 1rem !important;
        }
        
        @media (min-width: 768px) {
            .content { padding: 2rem; }
            .header { padding: 1.5rem 2rem; }
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="<?= base_url('portal/dashboard') ?>" class="back-btn">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 700; flex: 1;">Pilih Pembayaran</h1>
        <!-- Theme toggle will be positioned separately -->
    </div>
    
    <div class="content">
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>
        
        <div class="invoice-card">
            <div class="amount-label">Total Tagihan</div>
            <div class="amount-value">Rp <?= number_format($invoice['amount'], 0, ',', '.') ?></div>
            <div class="invoice-number">#<?= $invoice['invoice_number'] ?></div>
        </div>
        
        <form action="<?= base_url('portal/processPayment') ?>" method="POST" id="paymentForm">
            <input type="hidden" name="invoice_id" value="<?= $invoice['id'] ?>">
            <input type="hidden" name="method" id="selectedMethod" value="">
            
            <?php if (empty($groupedChannels)): ?>
                <div style="text-align: center; color: var(--text-muted); padding: 3rem 1rem;">
                    <i class="fas fa-exclamation-circle" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <p>Metode pembayaran tidak tersedia saat ini.</p>
                </div>
            <?php else: ?>
                <?php foreach ($groupedChannels as $groupName => $channels): ?>
                    <div class="channel-group">
                        <div class="channel-group-title"><?= $groupName ?></div>
                        <?php foreach ($channels as $chn): ?>
                            <label class="channel-item" onclick="selectChannel(this, '<?= $chn['code'] ?>')">
                                <img src="<?= $chn['icon_url'] ?>" alt="<?= $chn['name'] ?>" class="channel-logo">
                                <div class="channel-info">
                                    <span class="channel-name"><?= $chn['name'] ?></span>
                                    <span class="channel-fee">
                                        <?php if(isset($chn['total_fee']['flat']) && $chn['total_fee']['flat'] > 0): ?>
                                            + Biaya Admin Rp <?= number_format($chn['total_fee']['flat'], 0, ',', '.') ?>
                                        <?php else: ?>
                                            Bebas Biaya Admin
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <i class="fas fa-check-circle check-icon"></i>
                                <input type="radio" name="payment_method" value="<?= $chn['code'] ?>">
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                
                <div class="tos-box">
                    <label>
                        <input type="checkbox" id="tosCheck" onchange="checkFormValidity()" style="margin-top: 0.25rem;">
                        <span>
                            Saya menyetujui <a href="<?= base_url('portal/tos') ?>" target="_blank">Syarat & Ketentuan</a> yang berlaku.
                        </span>
                    </label>
                </div>
                
                <button type="submit" class="btn-pay" id="payButton" disabled>
                    <i class="fas fa-credit-card"></i> Bayar Sekarang
                </button>
            <?php endif; ?>
        </form>
        
        <div class="secure-badge">
            <i class="fas fa-lock"></i> Pembayaran Aman & Terverifikasi
        </div>
    </div>
    
    <script>
        function selectChannel(element, code) {
            document.querySelectorAll('.channel-item').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('selectedMethod').value = code;
            checkFormValidity();
        }
        
        function checkFormValidity() {
            const method = document.getElementById('selectedMethod').value;
            const tosChecked = document.getElementById('tosCheck').checked;
            const btn = document.getElementById('payButton');
            btn.disabled = !(method && tosChecked);
        }
    </script>
    
    <?= $this->include('portal/_mobile_nav') ?>
</body>
</html>
