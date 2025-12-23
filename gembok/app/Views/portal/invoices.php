<?php
/**
 * Portal Invoices - list of customer invoices
 */
?>
<?= $this->extend('layout') ?>

<?= $this->section('title') ?>Tagihan - Portal Pelanggan<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card">
    <h2>📄 Daftar Tagihan - <?= esc($customer['name'] ?? 'Pelanggan') ?></h2>
    
    <?php if (empty($invoices)): ?>
        <p>Tidak ada tagihan.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Deskripsi</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $inv): ?>
                <tr>
                    <td><?= esc($inv['created_at'] ?? '-') ?></td>
                    <td><?= esc($inv['description'] ?? '-') ?></td>
                    <td>Rp <?= number_format($inv['amount'] ?? 0, 0, ',', '.') ?></td>
                    <td>
                        <span class="badge <?= ($inv['status'] ?? '') === 'paid' ? 'badge-success' : 'badge-warning' ?>">
                            <?= esc(ucfirst($inv['status'] ?? 'pending')) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <a href="<?= base_url('portal?phone=' . urlencode($customer['phone'])) ?>" class="btn">← Kembali</a>
</div>

<style>
.data-table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
.data-table th, .data-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color, #eee); }
.data-table th { background: var(--bg-secondary, #f5f5f5); }
.badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem; }
.badge-success { background: #10b981; color: white; }
.badge-warning { background: #f59e0b; color: white; }
.btn { display: inline-block; padding: 0.5rem 1rem; background: var(--primary, #3b82f6); color: white; text-decoration: none; border-radius: 6px; }
</style>
<?= $this->endSection() ?>
