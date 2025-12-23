<?php
/**
 * Portal Edit WiFi - form to edit SSID and password
 */
?>
<?= $this->extend('layout') ?>

<?= $this->section('title') ?>Edit WiFi - Portal Pelanggan<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card" style="max-width: 500px; margin: 0 auto;">
    <h2>📶 Edit WiFi</h2>
    <p>Pelanggan: <strong><?= esc($customer['name'] ?? 'Pelanggan') ?></strong></p>
    
    <form method="post" action="<?= base_url('portal/wifi?phone=' . urlencode($customer['phone'])) ?>">
        <div class="form-group">
            <label for="ssid">SSID (Nama WiFi)</label>
            <input type="text" id="ssid" name="ssid" value="<?= esc($current['ssid'] ?? '') ?>" placeholder="Nama WiFi baru" required>
        </div>
        
        <div class="form-group">
            <label for="wifi_password">Password WiFi</label>
            <input type="password" id="wifi_password" name="wifi_password" placeholder="Password baru (min. 8 karakter)" minlength="8" required>
        </div>
        
        <button type="submit" class="btn btn-primary">💾 Simpan</button>
        <a href="<?= base_url('portal?phone=' . urlencode($customer['phone'])) ?>" class="btn btn-secondary">← Batal</a>
    </form>
</div>

<style>
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
.form-group input { width: 100%; padding: 0.75rem; border: 1px solid var(--border-color, #ddd); border-radius: 6px; font-size: 1rem; }
.btn { display: inline-block; padding: 0.75rem 1.5rem; border-radius: 6px; text-decoration: none; font-weight: 500; cursor: pointer; margin-right: 0.5rem; border: none; }
.btn-primary { background: #3b82f6; color: white; }
.btn-secondary { background: #6b7280; color: white; }
.btn:hover { opacity: 0.9; }
</style>
<?= $this->endSection() ?>
