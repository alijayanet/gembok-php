<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a0a0f">
    <title>Portal - <?= esc($customer['name']) ?></title>
    
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
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            padding: 0;
            overflow-x: hidden;
        }
        
        /* Animated background */
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
        
        /* Header - Mobile Optimized */
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
            flex-shrink: 0;
        }
        
        .header-info {
            flex: 1;
            min-width: 0;
        }
        
        .header-name {
            font-size: 1rem;
            font-weight: 700;
            color: var(--neon-cyan);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .header-subtitle {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-top: 0.125rem;
        }
        
        .logout-btn {
            padding: 0.625rem 1rem;
            background: rgba(255, 71, 87, 0.1);
            color: var(--neon-red);
            border: 1px solid var(--neon-red);
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.8125rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.375rem;
            transition: all 0.3s;
            flex-shrink: 0;
            white-space: nowrap;
        }
        
        .logout-btn:active {
            background: var(--neon-red);
            color: white;
            transform: scale(0.98);
        }
        
        /* Content */
        .content {
            padding: 1rem;
            padding-bottom: 2rem;
        }
        
        .section {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .section-title {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--neon-cyan);
        }
        
        .section-title i {
            font-size: 1.125rem;
        }
        
        /* Info Item */
        .info-item {
            margin-bottom: 0.875rem;
        }
        
        .info-item:last-child {
            margin-bottom: 0;
        }
        
        .info-label {
            color: var(--text-muted);
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            color: var(--text-primary);
            font-size: 1rem;
            font-weight: 600;
        }
        
        /* Badge */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.8125rem;
            font-weight: 600;
        }
        
        .badge-success {
            background: rgba(0, 255, 136, 0.15);
            color: var(--neon-green);
            border: 1px solid var(--neon-green);
        }
        
        .badge-danger {
            background: rgba(255, 71, 87, 0.15);
            color: var(--neon-red);
            border: 1px solid var(--neon-red);
        }
        
        .badge-warning {
            background: rgba(255, 235, 59, 0.15);
            color: var(--neon-yellow);
            border: 1px solid var(--neon-yellow);
        }
        
        /* Form - Mobile Optimized */
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.875rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--neon-cyan);
            background: rgba(255, 255, 255, 0.08);
        }
        
        /* Button - Touch Optimized */
        .btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--neon-cyan) 0%, var(--neon-purple) 100%);
            border: none;
            border-radius: 8px;
            color: var(--bg-primary);
            font-size: 0.9375rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            touch-action: manipulation;
        }
        
        .btn:active {
            transform: scale(0.98);
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Alert */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 0.875rem;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert i {
            font-size: 1.125rem;
            margin-top: 0.125rem;
        }
        
        .alert-success {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid var(--neon-green);
            color: var(--neon-green);
        }
        
        .alert-error {
            background: rgba(255, 71, 87, 0.1);
            border: 1px solid var(--neon-red);
            color: var(--neon-red);
        }
        
        /* Invoice Table - Mobile Optimized */
        .invoice-list {
            margin-top: 1rem;
        }
        
        .invoice-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.875rem;
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
        }
        
        .invoice-due {
            color: var(--text-secondary);
        }
        
        /* Divider */
        .divider {
            height: 1px;
            background: var(--border-color);
            margin: 1rem 0;
        }
        
        /* Loading State */
        .spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Tablet and Desktop */
        @media (min-width: 768px) {
            .content {
                max-width: 600px;
                margin: 0 auto;
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
    <div class="header">
        <div class="header-title">
            <div class="header-icon">
                <i class="fas fa-user"></i>
            </div>
            <div class="header-info">
                <div class="header-name"><?= esc($customer['name']) ?></div>
                <div class="header-subtitle">PPPoE: <?= esc($customer['pppoe_username'] ?? '-') ?></div>
            </div>
            <a href="<?= base_url('logout') ?>" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>

    <!-- Content -->
    <div class="content">
        <!-- Alerts -->
        <div id="success-alert" class="alert alert-success" style="display:none;">
            <i class="fas fa-check-circle"></i>
            <span id="success-message"></span>
        </div>
        
        <div id="error-alert" class="alert alert-error" style="display:none;">
            <i class="fas fa-exclamation-circle"></i>
            <span id="error-message"></span>
        </div>

        <!-- Package Info -->
        <div class="section">
            <div class="section-title">
                <i class="fas fa-box"></i>
                Paket Internet
            </div>
            <div class="info-item">
                <div class="info-label">Paket</div>
                <div class="info-value"><?= esc($package['name'] ?? 'Tidak ada paket') ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Harga</div>
                <div class="info-value">Rp <?= number_format($package['price'] ?? 0, 0, ',', '.') ?>/bulan</div>
            </div>
            <div class="info-item">
                <div class="info-label">Status</div>
                <div>
                    <?php if ($customer['status'] === 'active'): ?>
                        <span class="badge badge-success">
                            <i class="fas fa-check-circle"></i> Aktif
                        </span>
                    <?php elseif ($customer['status'] === 'isolated'): ?>
                        <span class="badge badge-danger">
                            <i class="fas fa-exclamation-circle"></i> Isolir
                        </span>
                    <?php else: ?>
                        <span class="badge badge-warning">
                            <i class="fas fa-pause-circle"></i> <?= ucfirst($customer['status']) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Payment Status -->
        <div class="section">
            <div class="section-title">
                <i class="fas fa-credit-card"></i>
                Status Pembayaran
            </div>
            <?php if ($currentInvoice): ?>
                <div class="info-item">
                    <div class="info-label">Tagihan Bulan Ini</div>
                    <div class="info-value">Rp <?= number_format($currentInvoice['amount'], 0, ',', '.') ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Jatuh Tempo</div>
                    <div class="info-value"><?= date('d M Y', strtotime($currentInvoice['due_date'])) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div>
                        <?php if ($currentInvoice['status'] === 'paid'): ?>
                            <span class="badge badge-success">
                                <i class="fas fa-check-circle"></i> Lunas
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($currentInvoice['status'] !== 'paid'): ?>
                    <div style="margin-top: 1rem;">
                        <a href="<?= base_url('portal/payment/' . $currentInvoice['id']) ?>" class="btn" style="width: 100%; text-decoration: none; text-align: center; display: block;">
                            <i class="fas fa-money-bill-wave"></i> Bayar Sekarang
                        </a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-success" style="margin: 0">
                    <i class="fas fa-info-circle"></i>
                    <span>Tidak ada tagihan yang harus dibayar saat ini.</span>
                </div>
            <?php endif; ?>
            
            <!-- Invoice History -->
            <?php if (!empty($invoices) && count($invoices) > 1): ?>
                <div class="divider"></div>
                <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.75rem; font-weight: 600">
                    Riwayat Tagihan
                </div>
                <div class="invoice-list">
                    <?php foreach ($invoices as $inv): ?>
                        <div class="invoice-item">
                            <div class="invoice-header">
                                <div class="invoice-month"><?= date('F Y', strtotime($inv['created_at'])) ?></div>
                                <div class="invoice-amount">Rp <?= number_format($inv['amount'], 0, ',', '.') ?></div>
                            </div>
                            <div class="invoice-footer">
                                <div class="invoice-due">Jatuh Tempo: <?= date('d M Y', strtotime($inv['due_date'])) ?></div>
                                <?php if ($inv['status'] === 'paid'): ?>
                                    <span class="badge badge-success" style="font-size: 0.6875rem; padding: 0.25rem 0.5rem">Lunas</span>
                                <?php else: ?>
                                    <span class="badge badge-warning" style="font-size: 0.6875rem; padding: 0.25rem 0.5rem">Pending</span>
                                    <a href="<?= base_url('portal/payment/' . $inv['id']) ?>" style="background: var(--neon-cyan); color: #000; padding: 0.2rem 0.6rem; border-radius: 4px; text-decoration: none; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.25rem; margin-left: 0.5rem;"><i class="fas fa-arrow-right"></i> Bayar</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ONU Status -->
        <div class="section" id="onu-section">
            <div class="section-title">
                <i class="fas fa-router"></i>
                Informasi ONU
            </div>
            <?php if ($onuData): ?>
                <div class="info-item">
                    <div class="info-label">Serial Number</div>
                    <div class="info-value"><?= esc($onuData['serial']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Model</div>
                    <div class="info-value"><?= esc($onuData['model']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div>
                        <?php if ($onuData['online']): ?>
                            <span class="badge badge-success">
                                <i class="fas fa-circle"></i> Online
                            </span>
                        <?php else: ?>
                            <span class="badge badge-danger">
                                <i class="fas fa-circle"></i> Offline
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">RX Power</div>
                    <div class="info-value"><?= esc($onuData['rxPower']) ?> dBm</div>
                </div>
            <?php else: ?>
                <div class="alert alert-error" style="margin: 0">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Perangkat ONU belum terhubung ke sistem.</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- WiFi Settings -->
        <div class="section" id="wifi-section">
            <div class="section-title">
                <i class="fas fa-wifi"></i>
                Pengaturan WiFi
            </div>
            
            <?php if ($onuData && $onuData['online']): ?>
                <!-- SSID -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-signature"></i> SSID WiFi (Nama Jaringan)
                    </label>
                    <input type="text" id="ssid-input" class="form-control" value="<?= esc($onuData['ssid']) ?>" placeholder="Masukkan SSID baru">
                    <div style="color: var(--text-muted); margin-top: 0.5rem; font-size: 0.75rem">Minimal 3 karakter</div>
                </div>
                <button type="button" class="btn" onclick="updateSsid()" id="ssid-btn">
                    <i class="fas fa-save"></i>
                    Simpan SSID
                </button>

                <div class="divider"></div>

                <!-- Password -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-key"></i> Password WiFi
                    </label>
                    <input type="text" id="password-input" class="form-control" value="<?= esc($onuData['wifiPassword']) ?>" placeholder="Masukkan password baru">
                    <div style="color: var(--text-muted); margin-top: 0.5rem; font-size: 0.75rem">Minimal 8 karakter</div>
                </div>
                <button type="button" class="btn" onclick="updatePassword()" id="password-btn">
                    <i class="fas fa-save"></i>
                    Simpan Password WiFi
                </button>
            <?php else: ?>
                <div class="alert alert-error" style="margin: 0">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Perangkat Tidak Terhubung</strong><br>
                        <span style="font-size: 0.875rem;">ONU Anda sedang offline atau belum terhubung dengan sistem. Silakan hubungi admin jika masalah berlanjut.</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Lapor Gangguan Section -->
        <div class="section" id="trouble-section">
            <div class="section-title">
                <i class="fas fa-tools"></i>
                Lapor Gangguan
            </div>
            <div class="form-group">
                <label class="form-label">Deskripsi Masalah</label>
                <textarea id="trouble-desc" class="form-control" rows="3" placeholder="Contoh: Lampu LOS merah, Internet Lambat, dll..."></textarea>
            </div>
            <button type="button" class="btn" onclick="submitTrouble()" id="trouble-btn" style="background: linear-gradient(135deg, #ff6b35 0%, #ff8c42 100%);">
                <i class="fas fa-paper-plane"></i>
                Kirim Laporan
            </button>
        </div>

        <!-- Account Settings -->
        <div class="section" id="account-section">
            <div class="section-title">
                <i class="fas fa-user-cog"></i>
                Pengaturan Akun Portal
            </div>
            <div class="form-group">
                <label class="form-label">Ganti Password Login (Portal)</label>
                <input type="password" id="portal-pass-input" class="form-control" placeholder="Password Baru">
                <div style="color: var(--text-muted); margin-top: 0.5rem; font-size: 0.75rem">Kosongkan jika tidak ingin mengganti</div>
            </div>
            <button type="button" class="btn" onclick="updatePortalPassword()" id="portal-pass-btn" style="background: linear-gradient(135deg, #FF9800 0%, #F44336 100%);">
                <i class="fas fa-key"></i>
                Simpan Password Baru
            </button>
        </div>
    </div>

    <script>
        function showAlert(type, message) {
            // Hide all alerts
            document.getElementById('success-alert').style.display = 'none';
            document.getElementById('error-alert').style.display = 'none';
            
            // Show specific alert
            if (type === 'success') {
                document.getElementById('success-message').textContent = message;
                document.getElementById('success-alert').style.display = 'flex';
            } else {
                document.getElementById('error-message').textContent = message;
                document.getElementById('error-alert').style.display = 'flex';
            }
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                document.getElementById('success-alert').style.display = 'none';
                document.getElementById('error-alert').style.display = 'none';
            }, 5000);
        }
        
        async function updateSsid() {
            const ssidInput = document.getElementById('ssid-input');
            const ssidBtn = document.getElementById('ssid-btn');
            const newSsid = ssidInput.value.trim();
            
            if (newSsid.length < 3) {
                showAlert('error', 'SSID minimal 3 karakter');
                return;
            }
            
            // Disable button
            ssidBtn.disabled = true;
            ssidBtn.innerHTML = '<i class="fas fa-spinner spinner"></i> Menyimpan...';
            
            try {
                const response = await fetch('<?= base_url('portal/updateSsid') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'ssid=' + encodeURIComponent(newSsid)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', result.message);
                } else {
                    showAlert('error', result.message);
                }
            } catch (error) {
                showAlert('error', 'Terjadi kesalahan: ' + error.message);
            } finally {
                // Re-enable button
                ssidBtn.disabled = false;
                ssidBtn.innerHTML = '<i class="fas fa-save"></i> Simpan SSID';
            }
        }
        
        async function updatePassword() {
            const passwordInput = document.getElementById('password-input');
            const passwordBtn = document.getElementById('password-btn');
            const newPassword = passwordInput.value.trim();
            
            if (newPassword.length < 8) {
                showAlert('error', 'Password minimal 8 karakter');
                return;
            }
            
            // Disable button
            passwordBtn.disabled = true;
            passwordBtn.innerHTML = '<i class="fas fa-spinner spinner"></i> Menyimpan...';
            
            try {
                const response = await fetch('<?= base_url('portal/updatePassword') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'password=' + encodeURIComponent(newPassword)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', result.message);
                } else {
                    showAlert('error', result.message);
                }
            } catch (error) {
                showAlert('error', 'Terjadi kesalahan: ' + error.message);
            } finally {
                // Re-enable button
                passwordBtn.disabled = false;
                passwordBtn.innerHTML = '<i class="fas fa-save"></i> Simpan Password';
            }
        }
        
        async function updatePortalPassword() {
            const passInput = document.getElementById('portal-pass-input');
            const passBtn = document.getElementById('portal-pass-btn');
            const newPass = passInput.value.trim();
            
            if (newPass.length === 0) {
                showAlert('error', 'Password tidak boleh kosong');
                return;
            }
            
            // Disable button
            passBtn.disabled = true;
            passBtn.innerHTML = '<i class="fas fa-spinner spinner"></i> Menyimpan...';
            
            try {
                const response = await fetch('<?= base_url('portal/changePortalPassword') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'portal_password=' + encodeURIComponent(newPass)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', result.message);
                    passInput.value = ''; // Reset input
                } else {
                    showAlert('error', result.message);
                }
            } catch (error) {
                showAlert('error', 'Terjadi kesalahan: ' + error.message);
            } finally {
                // Re-enable button
                passBtn.disabled = false;
                passBtn.innerHTML = '<i class="fas fa-key"></i> Simpan Password Baru';
        }
        }
        async function submitTrouble() {
            const descInput = document.getElementById('trouble-desc');
            const troubleBtn = document.getElementById('trouble-btn');
            const desc = descInput.value.trim();
            
            if (desc.length === 0) {
                showAlert('error', 'Silakan isi deskripsi masalah');
                return;
            }
            
            troubleBtn.disabled = true;
            troubleBtn.innerHTML = '<i class="fas fa-spinner spinner"></i> Mengirim...';
            
            try {
                const response = await fetch('<?= base_url('portal/reportTrouble') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'description=' + encodeURIComponent(desc)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', result.message);
                    descInput.value = '';
                } else {
                    showAlert('error', result.message);
                }
            } catch (error) {
                showAlert('error', 'Terjadi kesalahan: ' + error.message);
            } finally {
                troubleBtn.disabled = false;
                troubleBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Kirim Laporan';
            }
        }
    </script>
    
    <?= $this->include('portal/_mobile_nav') ?>
</body>
</html>
