<?php
/**
 * Invoices View - List of Invoices
 */

$totalInvoices = count($invoices ?? []);
$paidInvoices = count(array_filter($invoices ?? [], fn($i) => ($i['paid'] ?? 0) == 1));
$unpaidInvoices = $totalInvoices - $paidInvoices;
?>
<?= $this->extend('layout') ?>

<?= $this->section('title') ?>Data Invoice - Gembok Admin<?= $this->endSection() ?>
<?= $this->section('page_title') ?>Data Invoice & Tagihan<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php if (session()->getFlashdata('msg')): ?>
<div class="alert alert-success" style="margin-bottom: 1.5rem;">
    <i class="fas fa-check-circle"></i> <?= session()->getFlashdata('msg') ?>
</div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger" style="margin-bottom: 1.5rem; background: rgba(255, 59, 48, 0.1); border: 1px solid var(--neon-red); color: var(--neon-red);">
    <i class="fas fa-exclamation-circle"></i> <?= session()->getFlashdata('error') ?>
</div>
<?php endif; ?>

<!-- Stats Grid -->
<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="fas fa-file-invoice-dollar"></i>
        </div>
        <div class="stat-info">
            <h3><?= $totalInvoices ?></h3>
            <p>Total Invoice</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-check"></i>
        </div>
        <div class="stat-info">
            <h3><?= $paidInvoices ?></h3>
            <p>Lunas (Paid)</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-info">
            <h3><?= $unpaidInvoices ?></h3>
            <p>Belum Bayar</p>
        </div>
    </div>
     <div class="stat-card">
        <div class="stat-icon cyan">
            <i class="fas fa-file-download"></i>
        </div>
        <div class="stat-info">
             <a href="#" style="color: inherit; text-decoration: none;"><h3>Export</h3></a>
            <p>Laporan PDF/Excel</p>
        </div>
    </div>
</div>

<!-- Invoice List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history"></i> Riwayat Tagihan</h3>
        <div class="header-actions">
            <!-- Generate Button -->
            <form action="<?= base_url('admin/billing/generate') ?>" method="post" style="display: inline-block;" onsubmit="return confirm('Generate invoice untuk semua pelanggan aktif bulan ini?');">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-magic"></i> Generate Invoice Bulan Ini
                </button>
            </form>
        </div>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#Invoice</th>
                    <th>Pelanggan</th>
                    <th>Periode</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                    <th>Jatuh Tempo</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                         <i class="fas fa-file-invoice" style="font-size: 2rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                        Belum ada data invoice
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td>
                            <code style="color: var(--neon-cyan);">INV-<?= str_pad($inv['id'], 6, '0', STR_PAD_LEFT) ?></code>
                        </td>
                        <td>
                            <strong><?= esc($inv['customer_name']) ?></strong><br>
                            <small class="text-muted"><?= esc($inv['pppoe_username']) ?></small>
                        </td>
                        <td><?= date('F Y', strtotime($inv['created_at'])) ?></td>
                        <td>
                            <strong style="color: var(--neon-green);">Rp <?= number_format($inv['amount'], 0, ',', '.') ?></strong>
                        </td>
                        <td>
                            <?php if (($inv['paid'] ?? 0) == 1): ?>
                                <span class="badge badge-success">Lunas</span>
                            <?php elseif (($inv['status'] ?? '') == 'cancelled'): ?>
                                <span class="badge badge-secondary">Batal</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Belum Bayar</span>
                                <?php if (strtotime($inv['due_date']) < time()): ?>
                                    <span class="badge badge-danger" style="margin-left: 0.25rem;">Telat</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d M Y', strtotime($inv['due_date'])) ?></td>
                        <td>
                            <div style="display: flex; gap: 0.25rem;">
                                <?php if (($inv['paid'] ?? 0) == 0): ?>
                                    <form action="<?= base_url('admin/billing/pay/' . $inv['id']) ?>" method="post" onsubmit="return confirm('Konfirmasi pembayaran untuk <?= esc($inv['customer_name']) ?>?');" style="display:inline;">
                                        <button type="submit" class="btn btn-primary btn-sm" title="Bayar Lunas & Aktifkan">
                                            <i class="fas fa-check"></i> Bayar
                                        </button>
                                    </form>

                                    <!-- Buka & Tunda (Unisolate Only) -->
                                    <form action="<?= base_url('admin/billing/unisolate_only/' . $inv['id']) ?>" method="post" onsubmit="return confirm('Buka isolir SEMENTARA tanpa mengubah status bayar? Tagihan tetap BELUM LUNAS.');" style="display:inline;">
                                        <button type="submit" class="btn btn-secondary btn-sm" title="Buka Isolir Saja (Tagihan Ditunda)" style="background: var(--neon-purple); border-color: var(--neon-purple);">
                                            <i class="fas fa-unlock"></i> Buka
                                        </button>
                                    </form>

                                    <!-- Tombol Cancel -->
                                    <form action="<?= base_url('admin/billing/cancel/' . $inv['id']) ?>" method="post" onsubmit="return confirm('Batalkan invoice ini? Status akan berubah menjadi Batal.');" style="display:inline;">
                                        <button type="submit" class="btn btn-secondary btn-sm" title="Batalkan Invoice" style="background: var(--neon-red); border-color: var(--neon-red);">
                                            <i class="fas fa-times"></i> Batal
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <button class="btn btn-secondary btn-sm" title="Cetak" onclick="window.open('<?= base_url('admin/billing/print/' . $inv['id']) ?>', 'PrintInvoice', 'width=400,height=600')">
                                    <i class="fas fa-print"></i>
                                </button>
                                <button class="btn btn-secondary btn-sm" title="Kirim WA"><i class="fab fa-whatsapp"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
