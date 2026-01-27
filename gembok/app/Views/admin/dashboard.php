<?php
/**
 * Admin Dashboard - Dark Neon Theme
 */
?>
<?= $this->extend('layout') ?>

<?= $this->section('title') ?>Dashboard - Gembok Admin<?= $this->endSection() ?>
<?= $this->section('page_title') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Stats Grid -->
<!-- Stats Grid -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
    <!-- 1. Devices (GenieACS) -->
    <div class="stat-card">
        <div class="stat-icon cyan">
            <i class="fas fa-satellite-dish"></i>
        </div>
        <div class="stat-info">
            <h3><?= $stats['totalDevices'] ?? 0 ?></h3>
            <p>Total ONU/Devices</p>
        </div>
    </div>
    
    <!-- 2. Online PPPoE (MikroTik) -->
    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="fas fa-plug"></i>
        </div>
        <div class="stat-info">
            <h3>
                <?= $stats['onlinePppoe'] ?? 0 ?>
                <?php if(!$stats['mikrotikConnected']): ?>
                    <small class="text-danger" style="font-size:0.6em">(DB Only)</small>
                <?php endif; ?>
            </h3>
            <p>Online PPPoE</p>
        </div>
    </div>
    
    <!-- 3. Online Hotspot (MikroTik) -->
    <div class="stat-card">
        <div class="stat-icon pink">
            <i class="fas fa-wifi"></i>
        </div>
        <div class="stat-info">
            <h3>
                <?= $stats['onlineHotspot'] ?? 0 ?>
                <?php if(!$stats['mikrotikConnected']): ?>
                    <small class="text-danger" style="font-size:0.6em">(DB Only)</small>
                <?php endif; ?>
            </h3>
            <p>User Hotspot</p>
        </div>
    </div>
    
    <?php if (session()->get('admin_role') === 'admin'): ?>
    <!-- 4. Pending Invoices -->
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-file-invoice-dollar"></i>
        </div>
        <div class="stat-info">
            <h3><?= $stats['pendingInvoices'] ?? 0 ?></h3>
            <p>Invoice Belum Bayar</p>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (session()->get('admin_role') === 'admin'): ?>
    <!-- 5. Today's Revenue -->
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-wallet"></i>
        </div>
        <div class="stat-info">
            <h3 style="font-size: 1.5rem;">Rp <?= number_format($stats['todayRevenue'] ?? 0, 0, ',', '.') ?></h3>
            <p>Pendapatan Hari Ini</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- 6. Pending Tickets -->
    <div class="stat-card">
        <div class="stat-icon yellow">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-info">
            <h3><?= $stats['pendingTickets'] ?? 0 ?></h3>
            <p>Tiket Gangguan</p>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-bolt"></i> Menu Cepat</h3>
        </div>
        <div class="quick-actions">
            <a href="<?= base_url('admin/map') ?>" class="action-card">
                <i class="fas fa-map-marked-alt"></i>
                <span>Peta ONU</span>
            </a>
            <?php if (session()->get('admin_role') === 'admin'): ?>
            <a href="<?= base_url('admin/billing/invoices') ?>" class="action-card">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Invoice</span>
            </a>
            <a href="<?= base_url('admin/billing/customers') ?>" class="action-card">
                <i class="fas fa-users"></i>
                <span>Pelanggan</span>
            </a>
            <a href="<?= base_url('admin/billing/packages') ?>" class="action-card">
                <i class="fas fa-box"></i>
                <span>Paket</span>
            </a>
            <?php endif; ?>
            <?php if (session()->get('admin_role') === 'admin'): ?>
            <a href="<?= base_url('admin/tickets') ?>" class="action-card">
                <i class="fas fa-headset"></i>
                <span>Tiket (Admin)</span>
            </a>
            <?php endif; ?>
            <a href="<?= base_url('admin/trouble') ?>" class="action-card">
                <i class="fas fa-tools"></i>
                <span>Daftar Laporan</span>
            </a>
            <?php if (session()->get('admin_role') === 'technician'): ?>
            <a href="<?= base_url('admin/technician/genieacs') ?>" class="action-card">
                <i class="fas fa-satellite-dish"></i>
                <span>Cek Signal</span>
            </a>
            <?php endif; ?>
            <?php if (session()->get('admin_role') === 'admin'): ?>
            <a href="<?= base_url('admin/settings') ?>" class="action-card">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Command Terminal -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-terminal"></i> Terminal Perintah</h3>
        </div>
        <div class="terminal">
            <form id="commandForm">
                <div class="terminal-input">
                    <input type="text" id="commandInput" placeholder="Ketik perintah: REBOOT, PPPOE-ON, PPPOE-OFF" />
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-paper-plane"></i> Kirim
                    </button>
                </div>
            </form>
            <div id="commandOutput" class="terminal-output">$ waiting for command...</div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-clock"></i> Aktivitas Terbaru</h3>
        <button class="btn btn-secondary btn-sm">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Waktu</th>
                <th>Aksi</th>
                <th>Device</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= date('H:i:s') ?></td>
                <td>System Started</td>
                <td>Dashboard</td>
                <td><span class="badge badge-success">OK</span></td>
            </tr>
            <tr>
                <td><?= date('H:i:s', strtotime('-5 minutes')) ?></td>
                <td>Database Connected</td>
                <td>MySQL</td>
                <td><span class="badge badge-success">OK</span></td>
            </tr>
            <tr>
                <td><?= date('H:i:s', strtotime('-10 minutes')) ?></td>
                <td>ONU Data Synced</td>
                <td>GenieACS</td>
                <td><span class="badge badge-info">Synced</span></td>
            </tr>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.getElementById('commandForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const input = document.getElementById('commandInput');
    const output = document.getElementById('commandOutput');
    const cmd = input.value.trim();
    if (!cmd) return;
    
    output.textContent = '$ ' + cmd + '\n⏳ Processing...';
    try {
        const res = await fetch('<?= base_url('admin/command') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'command=' + encodeURIComponent(cmd)
        });
        const data = await res.json();
        output.textContent = '$ ' + cmd + '\n' + (data.msg || '✅ OK');
    } catch (err) {
        output.textContent = '$ ' + cmd + '\n❌ Error: ' + err.message;
    }
    input.value = '';
});
</script>
<?= $this->endSection() ?>
