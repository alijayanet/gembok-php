<?php
/**
 * Portal Edit WiFi Result - shows result after WiFi update
 */
?>
<?= $this->extend('layout') ?>

<?= $this->section('title') ?>Hasil Edit WiFi<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card" style="max-width: 500px; margin: 0 auto; text-align: center;">
    <h2>📶 Hasil Update WiFi</h2>
    
    <div class="result-message <?= strpos($message ?? '', '✅') !== false ? 'success' : 'error' ?>">
        <?= esc($message ?? 'Proses selesai') ?>
    </div>
    
    <a href="<?= base_url('portal?phone=' . urlencode($customer['phone'])) ?>" class="btn btn-primary">← Kembali ke Dashboard</a>
</div>

<style>
.result-message { padding: 2rem; border-radius: 8px; margin: 1.5rem 0; font-size: 1.25rem; }
.result-message.success { background: rgba(16, 185, 129, 0.1); color: #059669; }
.result-message.error { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
.btn-primary { display: inline-block; padding: 0.75rem 1.5rem; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; }
</style>
<?= $this->endSection() ?>
