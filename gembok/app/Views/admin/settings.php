<?php
/**
 * Admin Settings View - Dark Neon Theme
 */
?>
<?= $this->extend('layout') ?>

<?= $this->section('title') ?>Pengaturan - Gembok Admin<?= $this->endSection() ?>
<?= $this->section('page_title') ?>Pengaturan Sistem<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <!-- WhatsApp Settings -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fab fa-whatsapp" style="color: #25d366;"></i> WhatsApp Business Cloud API</h3>
        </div>
        <form action="<?= base_url('admin/settings/save') ?>" method="post">
            <div class="form-group">
                <label class="form-label">Phone Number ID (API URL)</label>
                <input type="text" name="WHATSAPP_API_URL" class="form-control" placeholder="123456789012345" value="<?= esc($WHATSAPP_API_URL ?? '') ?>">
                <small style="color: var(--text-muted)">Masukkan Phone Number ID</small>
            </div>
            <div class="form-group">
                <label class="form-label">Access Token</label>
                <input type="password" name="WHATSAPP_TOKEN" class="form-control" placeholder="••••••••••••" value="<?= esc($WHATSAPP_TOKEN ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Webhook Verify Token</label>
                <input type="text" name="WHATSAPP_VERIFY_TOKEN" class="form-control" placeholder="my_custom_token" value="<?= esc($WHATSAPP_VERIFY_TOKEN ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan WhatsApp
            </button>
        </form>
    </div>
    
    <!-- GenieACS Settings -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-server" style="color: var(--neon-cyan);"></i> GenieACS API</h3>
        </div>
        <form action="<?= base_url('admin/settings/save') ?>" method="post">
            <div class="form-group">
                <label class="form-label">API URL</label>
                <input type="url" name="GENIEACS_URL" class="form-control" placeholder="https://genieacs.example.com" value="<?= esc($GENIEACS_URL ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="GENIEACS_USERNAME" class="form-control" placeholder="admin" value="<?= esc($GENIEACS_USERNAME ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="GENIEACS_PASSWORD" class="form-control" placeholder="••••••••" value="<?= esc($GENIEACS_PASSWORD ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan GenieACS
            </button>
        </form>
    </div>
    
    <!-- MikroTik Settings -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-network-wired" style="color: var(--neon-purple);"></i> MikroTik RouterOS</h3>
        </div>
        <form action="<?= base_url('admin/settings/save') ?>" method="post">
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 0.75rem;">
                <div class="form-group">
                    <label class="form-label">Router IP/Host</label>
                    <input type="text" name="MIKROTIK_HOST" class="form-control" placeholder="192.168.88.1" value="<?= esc($MIKROTIK_HOST ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Port API</label>
                    <input type="number" name="MIKROTIK_PORT" class="form-control" placeholder="8728" value="<?= esc($MIKROTIK_PORT ?? '8728') ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="MIKROTIK_USER" class="form-control" placeholder="admin" value="<?= esc($MIKROTIK_USER ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="MIKROTIK_PASS" class="form-control" placeholder="••••••••" value="<?= esc($MIKROTIK_PASS ?? '') ?>">
            </div>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
                <i class="fas fa-info-circle"></i> Port default: 8728 (API), 8729 (API-SSL)
            </p>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan MikroTik
            </button>
        </form>
    </div>
    
    <!-- Tripay Settings -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-money-bill-wave" style="color: #00C9A7;"></i> Tripay Payment Gateway</h3>
        </div>
        <form action="<?= base_url('admin/settings/save') ?>" method="post">
            <div class="form-group">
                <label class="form-label">Merchant Code</label>
                <input type="text" name="TRIPAY_MERCHANT_CODE" class="form-control" placeholder="T12345" value="<?= esc($TRIPAY_MERCHANT_CODE ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">API Key</label>
                <input type="password" name="TRIPAY_API_KEY" class="form-control" placeholder="YOUR_API_KEY" value="<?= esc($TRIPAY_API_KEY ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Private Key</label>
                <input type="password" name="TRIPAY_PRIVATE_KEY" class="form-control" placeholder="YOUR_PRIVATE_KEY" value="<?= esc($TRIPAY_PRIVATE_KEY ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Mode</label>
                <select name="TRIPAY_MODE" class="form-control">
                    <option value="sandbox" <?= ($TRIPAY_MODE ?? '') === 'sandbox' ? 'selected' : '' ?>>Sandbox (Testing)</option>
                    <option value="production" <?= ($TRIPAY_MODE ?? '') === 'production' ? 'selected' : '' ?>>Production (Live)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan Tripay
            </button>
        </form>
    </div>

    <!-- Midtrans Settings -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-credit-card" style="color: #FF6B6B;"></i> Midtrans Payment Gateway</h3>
        </div>
        <form action="<?= base_url('admin/settings/save') ?>" method="post">
            <div class="form-group">
                <label class="form-label">Server Key</label>
                <input type="password" name="MIDTRANS_SERVER_KEY" class="form-control" placeholder="SB-Mid-server-..." value="<?= esc($MIDTRANS_SERVER_KEY ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Client Key</label>
                <input type="text" name="MIDTRANS_CLIENT_KEY" class="form-control" placeholder="SB-Mid-client-..." value="<?= esc($MIDTRANS_CLIENT_KEY ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Mode</label>
                <select name="MIDTRANS_MODE" class="form-control">
                    <option value="sandbox" <?= ($MIDTRANS_MODE ?? '') === 'sandbox' ? 'selected' : '' ?>>Sandbox (Testing)</option>
                    <option value="production" <?= ($MIDTRANS_MODE ?? '') === 'production' ? 'selected' : '' ?>>Production (Live)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan Midtrans
            </button>
        </form>
    </div>

    <!-- Webhook URLs -->
    <div class="card" style="grid-column: 1 / -1;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-webhook" style="color: var(--neon-pink);"></i> Webhook & Integration URLs</h3>
        </div>
        <div style="padding: 1.5rem;">
            <p style="color: var(--text-secondary); margin-bottom: 1.5rem; font-size:0.9rem;">
                <i class="fas fa-info-circle"></i> Copy URL ini dan paste ke gateway/payment provider Anda
            </p>
            
            <!-- WhatsApp Webhook -->
            <div class="webhook-item">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem;">
                    <div style="width: 40px; height: 40px; background: #25D366; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <i class="fab fa-whatsapp" style="color: white; font-size: 1.25rem;"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; color: var(--text-primary); font-size: 0.95rem;">WhatsApp Webhook</h4>
                        <p style="margin: 0; color: var(--text-muted); font-size: 0.8rem;">Paste ke Fonnte / WA Gateway dashboard</p>
                    </div>
                </div>
                <div style="display: flex; gap: 0.75rem; align-items: center; background: rgba(255,255,255,0.05); padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <code id="webhook-whatsapp" style="flex: 1; color: var(--neon-cyan); font-size: 0.85rem; word-break: break-all;"><?= esc($webhookUrls['whatsapp']) ?></code>
                    <button onclick="copyWebhook('webhook-whatsapp', this)" class="btn btn-sm btn-secondary">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
            </div>

            <!-- Payment Webhook (Tripay) -->
            <div class="webhook-item">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem;">
                    <div style="width: 40px; height: 40px; background: #00C9A7; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-money-bill-wave" style="color: white; font-size: 1.25rem;"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; color: var(--text-primary); font-size: 0.95rem;">Payment Webhook (Tripay)</h4>
                        <p style="margin: 0; color: var(--text-muted); font-size: 0.8rem;">Configure di Tripay callback URL</p>
                    </div>
                </div>
                <div style="display: flex; gap: 0.75rem; align-items: center; background: rgba(255,255,255,0.05); padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <code id="webhook-payment" style="flex: 1; color: var(--neon-cyan); font-size: 0.85rem; word-break: break-all;"><?= esc($webhookUrls['payment']) ?></code>
                    <button onclick="copyWebhook('webhook-payment', this)" class="btn btn-sm btn-secondary">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
            </div>

            <!-- Payment Webhook (Midtrans) -->
            <div class="webhook-item">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem;">
                    <div style="width: 40px; height: 40px; background: #FF6B6B; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-credit-card" style="color: white; font-size: 1.25rem;"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; color: var(--text-primary); font-size: 0.95rem;">Payment Webhook (Midtrans)</h4>
                        <p style="margin: 0; color: var(--text-muted); font-size: 0.8rem;">Configure di Midtrans notification URL</p>
                   </div>
                </div>
                <div style="display: flex; gap: 0.75rem; align-items: center; background: rgba(255,255,255,0.05); padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <code id="webhook-midtrans" style="flex: 1; color: var(--neon-cyan); font-size: 0.85rem; word-break: break-all;"><?= esc($webhookUrls['midtrans']) ?></code>
                    <button onclick="copyWebhook('webhook-midtrans', this)" class="btn btn-sm btn-secondary">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
            </div>

            <div style="background: rgba(52, 152, 219, 0.1); border-left: 3px solid var(--neon-cyan); padding: 1rem; border-radius: 6px; margin-top: 1.5rem;">
                <p style="color: var(--text-secondary); font-size: 0.85rem; margin: 0;">
                    <i class="fas fa-globe"></i> <strong>Base URL:</strong> <?= esc($baseUrl) ?>
                    <br><br>
                    <i class="fas fa-shield-alt"></i> Pastikan webhook endpoint dapat diakses dari internet. Untuk testing local, gunakan tools seperti ngrok.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
.webhook-item {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}
.webhook-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}
</style>

<script>
function copyWebhook(elementId, button) {
    const text = document.getElementById(elementId).textContent.trim();
    
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            showCopied(button);
        }).catch(err => {
            fallbackCopy(text, button);
        });
    } else {
        fallbackCopy(text, button);
    }
}

function fallbackCopy(text, button) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    
    try {
        document.execCommand('copy');
        showCopied(button);
    } catch (err) {
        alert('Failed to copy: ' + err);
    }
    
    document.body.removeChild(textarea);
}

function showCopied(button) {
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check"></i> Copied!';
    button.style.background = 'var(--neon-green)';
    button.style.borderColor = 'var(--neon-green)';
    
    setTimeout(() => {
        button.innerHTML = originalHTML;
        button.style.background = '';
        button.style.borderColor = '';
    }, 2000);
}
</script>

<?= $this->endSection() ?>
