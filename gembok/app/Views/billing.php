<?php
/**
 * Billing View - Dark Neon Theme
 */
?>
<?= $this->extend('layout') ?>

<?= $this->section('title') ?>Billing - Gembok Admin<?= $this->endSection() ?>
<?= $this->section('page_title') ?>Billing Management<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Stats Summary at TOP -->
<div class="stats-grid" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
            <h3>0</h3>
            <p>Sudah Bayar</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-info">
            <h3>0</h3>
            <p>Menunggu Bayar</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon cyan">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="stat-info">
            <h3>Rp 0</h3>
            <p>Total Pendapatan</p>
        </div>
    </div>
</div>

<!-- Customer List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-invoice-dollar"></i> Daftar Pelanggan & Tagihan</h3>
        <button class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Tambah Pelanggan
        </button>
    </div>
    
    <?php if (empty($customers)): ?>
        <div style="text-align: center; padding: 3rem;">
            <i class="fas fa-users" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
            <p style="color: var(--text-secondary);">Belum ada pelanggan terdaftar.</p>
            <a href="#" class="btn btn-primary" style="margin-top: 1rem;">
                <i class="fas fa-plus"></i> Tambah Pelanggan Pertama
            </a>
        </div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Telepon</th>
                    <th>PPPoE Username</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $c): ?>
                <tr>
                    <td>#<?= esc($c['id']) ?></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 36px; height: 36px; background: var(--gradient-primary); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.85rem;">
                                <?= strtoupper(substr($c['name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <?= esc($c['name']) ?>
                        </div>
                    </td>
                    <td><?= esc($c['phone']) ?></td>
                    <td><code style="background: rgba(0, 245, 255, 0.1); padding: 0.25rem 0.5rem; border-radius: 4px; color: var(--neon-cyan);"><?= esc($c['pppoe_username'] ?? '-') ?></code></td>
                    <td>
                        <span class="badge <?= ($c['status'] ?? '') === 'paid' ? 'badge-success' : 'badge-warning' ?>">
                            <?= esc(ucfirst($c['status'] ?? 'pending')) ?>
                        </span>
                    </td>
                    <td>
                        <form action="<?= base_url('billing/pay/' . $c['id']) ?>" method="post" style="display: inline;">
                            <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Tandai sebagai Lunas?')">
                                <i class="fas fa-check"></i> Bayar
                            </button>
                        </form>
                        <button class="btn btn-secondary btn-sm">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
