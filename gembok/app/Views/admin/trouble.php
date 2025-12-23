<?php
/**
 * Trouble Ticket / Laporan Gangguan View
 */
?>
<?= $this->extend('layout') ?>

<?= $this->section('title') ?>Laporan Gangguan - Gembok Admin<?= $this->endSection() ?>
<?= $this->section('page_title') ?>Laporan Gangguan<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Stats Row -->
<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card">
        <div class="stat-icon cyan">
            <i class="fas fa-ticket-alt"></i>
        </div>
        <div class="stat-info">
            <h3><?= count($tickets ?? []) ?></h3>
            <p>Total Laporan</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div class="stat-info">
            <h3>0</h3>
            <p>Pending</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="fas fa-tools"></i>
        </div>
        <div class="stat-info">
            <h3>0</h3>
            <p>In Progress</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
            <h3>0</h3>
            <p>Resolved</p>
        </div>
    </div>
</div>

<!-- Tickets Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-exclamation-triangle"></i> Daftar Laporan</h3>
        <div style="display: flex; gap: 0.5rem;">
            <select class="form-control" style="width: auto; padding: 0.5rem 1rem;">
                <option>Semua Status</option>
                <option>Pending</option>
                <option>In Progress</option>
                <option>Resolved</option>
            </select>
            <button class="btn btn-primary btn-sm" onclick="addTicket()">
                <i class="fas fa-plus"></i> Tambah Laporan
            </button>
        </div>
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Pelanggan</th>
                <th>Masalah</th>
                <th>Status</th>
                <th>Prioritas</th>
                <th>Tanggal</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tickets)): ?>
            <tr>
                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                    <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 1rem; display: block; color: var(--neon-green);"></i>
                    Tidak ada laporan gangguan
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($tickets as $ticket): ?>
                <tr>
                    <td>#<?= esc($ticket['id'] ?? '0') ?></td>
                    <td><?= esc($ticket['customer_name'] ?? 'N/A') ?></td>
                    <td><?= esc(substr($ticket['description'] ?? '', 0, 50)) ?>...</td>
                    <td>
                        <?php
                        $statusClass = 'badge-warning';
                        if (($ticket['status'] ?? '') === 'resolved') $statusClass = 'badge-success';
                        if (($ticket['status'] ?? '') === 'in_progress') $statusClass = 'badge-info';
                        ?>
                        <span class="badge <?= $statusClass ?>"><?= esc(ucfirst($ticket['status'] ?? 'pending')) ?></span>
                    </td>
                    <td>
                        <?php
                        $priorityClass = 'badge-info';
                        if (($ticket['priority'] ?? '') === 'high') $priorityClass = 'badge-danger';
                        if (($ticket['priority'] ?? '') === 'medium') $priorityClass = 'badge-warning';
                        ?>
                        <span class="badge <?= $priorityClass ?>"><?= esc(ucfirst($ticket['priority'] ?? 'low')) ?></span>
                    </td>
                    <td><?= esc($ticket['created_at'] ?? '-') ?></td>
                    <td>
                        <div style="display: flex; gap: 0.25rem;">
                            <button class="btn btn-secondary btn-sm" onclick="viewTicket(<?= $ticket['id'] ?? 0 ?>)" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-secondary btn-sm" onclick="resolveTicket(<?= $ticket['id'] ?? 0 ?>)" title="Resolve">
                                <i class="fas fa-check"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function addTicket() {
    alert('Fitur tambah laporan gangguan akan segera tersedia');
}

function viewTicket(id) {
    alert('View ticket #' + id);
}

function resolveTicket(id) {
    if (confirm('Tandai laporan #' + id + ' sebagai resolved?')) {
        alert('Resolve ticket #' + id);
    }
}
</script>
<?= $this->endSection() ?>
