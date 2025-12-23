<?= $this->extend('portal/dashboard') ?>

<?= $this->section('content') ?>
<!-- Override content for Payment Page, but keep layout if needed, or standalone page -->
<!-- Since dashboard.php is full page structure, we better create standalone payment page extending a base layout or just copying structure. 
     Let's verify dashboard.php structure quickly. It seems dashboard.php has HTML structure inside.
     So we should create a standalone file with full HTML structure for now to fit mobile portal theme. 
-->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Pembayaran - Gembok Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --bg-page: #f8fafc;
            --surface: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }

        .header {
            background: var(--surface);
            padding: 1rem 1.25rem;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .back-btn {
            color: var(--text-main);
            text-decoration: none;
            font-size: 1.1rem;
        }

        .header-title {
            font-weight: 600;
            font-size: 1.125rem;
        }

        .container {
            max-width: 480px;
            margin: 0 auto;
            padding: 1.25rem;
        }

        .invoice-card {
            background: var(--surface);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .amount-label {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }

        .amount-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: -0.5px;
        }

        .invoice-number {
            display: inline-block;
            background: #eff6ff;
            color: var(--primary);
            padding: 0.25rem 0.75rem;
            border-radius: 99px;
            font-size: 0.825rem;
            font-weight: 500;
            margin-top: 0.5rem;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-main);
        }

        .channel-group {
            margin-bottom: 1.5rem;
        }

        .channel-group-title {
            font-size: 0.825rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
        }

        .channel-list {
            display: grid;
            gap: 0.75rem;
        }

        .channel-item {
            display: flex;
            align-items: center;
            background: var(--surface);
            padding: 1rem;
            border-radius: 12px;
            border: 1px solid var(--border);
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }

        .channel-item:hover, .channel-item.selected {
            border-color: var(--primary);
            background: #eff6ff;
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
        }

        .channel-info {
            flex: 1;
        }

        .channel-name {
            font-weight: 600;
            font-size: 0.9375rem;
            color: var(--text-main);
            display: block;
        }
        
        .channel-fee {
            font-size: 0.75rem;
            color: var(--text-muted);
            display: block;
        }

        .check-icon {
            color: var(--primary);
            font-size: 1.25rem;
            opacity: 0;
            transform: scale(0.5);
            transition: all 0.2s;
        }

        .channel-item.selected .check-icon {
            opacity: 1;
            transform: scale(1);
        }

        .pay-btn-container {
            position: sticky;
            bottom: 0;
            padding: 1.25rem;
            background: var(--surface);
            border-top: 1px solid var(--border);
            margin: 0 -1.25rem -1.25rem;
        }

        .btn-pay {
            display: block;
            width: 100%;
            background: var(--primary);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            text-align: center;
            text-decoration: none;
        }

        .btn-pay:hover {
            background: var(--primary-dark);
        }
        
        .btn-pay:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
        }

        /* Error Message */
        .alert {
            background: #fee2e2;
            color: #991b1b;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

    </style>
</head>
<body>

    <div class="header">
        <a href="<?= base_url('portal') ?>" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <h1 class="header-title">Pilih Pembayaran</h1>
    </div>

    <div class="container">
    
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert">
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
                <div style="text-align: center; color: var(--text-muted); padding: 2rem;">
                    <i class="fas fa-exclamation-circle" style="font-size: 2rem; margin-bottom: 1rem; color: #cbd5e1;"></i>
                    <p>Metode pembayaran tidak tersedia saat ini.</p>
                </div>
            <?php else: ?>
                <?php foreach ($groupedChannels as $groupName => $channels): ?>
                    <div class="channel-group">
                        <div class="channel-group-title"><?= $groupName ?></div>
                        <div class="channel-list">
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
                    </div>
                <?php endforeach; ?>

                
                <!-- TOS Agreement -->
                <div style="margin: 1.5rem 0; padding: 1rem; background: #fffbeb; border-radius: 12px; border: 1px solid #fcd34d;">
                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; cursor: pointer;">
                        <input type="checkbox" id="tosCheck" onchange="checkFormValidity()" style="margin-top: 0.25rem;">
                        <span style="font-size: 0.9rem; color: var(--text-main);">
                            Saya menyetujui <a href="<?= base_url('portal/tos') ?>" target="_blank" style="color: var(--primary); text-decoration: underline;">Syarat & Ketentuan</a> yang berlaku.
                        </span>
                    </label>
                </div>

                <div class="pay-btn-container">
                    <button type="submit" class="btn-pay" id="payButton" disabled>
                        Bayar Sekarang <i class="fas fa-arrow-right" style="margin-left: 0.5rem; font-size: 0.8em;"></i>
                    </button>
                </div>
            <?php endif; ?>
        </form>
        
        <div style="text-align: center; margin-top: 1rem; color: var(--text-muted); font-size: 0.8rem;">
            <i class="fas fa-lock" style="margin-right: 0.25rem;"></i> Pembayaran Aman & Terverifikasi
        </div>

    </div>

    <script>
        function selectChannel(element, code) {
            // Remove selected class from all
            document.querySelectorAll('.channel-item').forEach(el => el.classList.remove('selected'));
            
            // Add selected class to clicked
            element.classList.add('selected');
            
            // Update hidden input
            document.getElementById('selectedMethod').value = code;
            
            // Re-check validity
            checkFormValidity();
        }

        function checkFormValidity() {
            const method = document.getElementById('selectedMethod').value;
            const tosChecked = document.getElementById('tosCheck').checked;
            const btn = document.getElementById('payButton');

            if (method && tosChecked) {
                btn.disabled = false;
            } else {
                btn.disabled = true;
            }
        }
    </script>
</body>
</html>
