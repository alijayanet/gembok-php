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
            <?php if (session()->get('admin_role') === 'admin'): ?>
            <button class="btn btn-primary btn-sm" onclick="addTicket()">
                <i class="fas fa-plus"></i> Tambah Laporan
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Pelanggan</th>
                    <th>Masalah</th>
                    <th>Teknisi</th>
                    <th>Status</th>
                    <th>Prioritas</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tickets)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">
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
                            <span style="color: var(--neon-cyan);">
                                <i class="fas fa-user-cog"></i> <?= esc($ticket['technician_name'] ?? 'Belum ditugaskan') ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $status = $ticket['status'] ?? 'pending';
                            $badge = match($status) {
                                'pending' => 'badge-danger',
                                'in_progress' => 'badge-warning',
                                'resolved' => 'badge-success',
                                default => 'badge-info'
                            };
                            ?>
                            <span class="badge <?= $badge ?>"><?= strtoupper($status) ?></span>
                        </td>
                        <td>
                            <?php 
                            $prio = $ticket['priority'] ?? 'low';
                            $prioStyle = match($prio) {
                                'high' => 'color: var(--neon-pink)',
                                'medium' => 'color: var(--neon-orange)',
                                default => 'color: var(--text-secondary)'
                            };
                            ?>
                            <span style="<?= $prioStyle ?>; font-weight: 500;"><?= strtoupper($prio) ?></span>
                        </td>
                        <td><?= date('d M Y H:i', strtotime($ticket['created_at'] ?? '')) ?></td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <?php if ($status !== 'resolved'): ?>
                                    <?php if (session()->get('admin_role') === 'admin'): ?>
                                    <button class="btn btn-sm btn-primary" onclick="showAssignModal(<?= $ticket['id'] ?>, '<?= esc($ticket['assigned_to'] ?? '') ?>')" title="Tugaskan Teknisi">
                                        <i class="fas fa-user-check"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-success" onclick="resolveTicket(<?= $ticket['id'] ?>)" title="Tandai Selesai">
                                        <i class="fas fa-check"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Tiket -->
<div id="addTicketModal" class="modal-overlay" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Buat Laporan Gangguan</h3>
            <button class="modal-close" onclick="closeModals()">&times;</button>
        </div>
        <form action="<?= base_url('admin/trouble/create') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Pilih Pelanggan</label>
                    <select name="customer_id" class="form-control" required>
                        <option value="">-- Pilih Pelanggan --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?> (<?= esc($c['pppoe_username']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Prioritas</label>
                    <select name="priority" class="form-control">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tugaskan Ke (Opsional)</label>
                    <select name="assigned_to" class="form-control">
                        <option value="">-- Pilih Teknisi (Jika ada) --</option>
                        <?php foreach ($technicians as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= esc($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Deskripsi Gangguan</label>
                    <textarea name="description" class="form-control" rows="4" placeholder="Jelaskan masalahnya..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModals()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Tiket</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Assign Teknisi -->
<div id="assignModal" class="modal-overlay" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Tugaskan Teknisi</h3>
            <button class="modal-close" onclick="closeModals()">&times;</button>
        </div>
        <form id="assignForm" method="POST">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Pilih Teknisi</label>
                    <select name="assigned_to" class="form-control" required id="select_technician">
                        <option value="">-- Pilih Teknisi --</option>
                        <?php foreach ($technicians as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= esc($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModals()">Batal</button>
                <button type="submit" class="btn btn-primary">Tugaskan Sekarang</button>
            </div>
        </form>
    </div>
</div>

    <style>
        .modal-overlay { position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); display:flex; align-items:center; justify-content:center; z-index:9999; }
        .modal { background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 12px; width: 90%; max-width: 500px; }
        .modal-header { padding: 1rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 1.5rem; }
        .modal-footer { padding: 1rem; border-top: 1px solid var(--border-color); display:flex; justify-content: flex-end; gap: 0.5rem; }
        .modal-close { background:none; border:none; font-size: 1.5rem; color: var(--text-muted); cursor:pointer; }
    </style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function addTicket() {
    document.getElementById('addTicketModal').style.display = 'flex';
}

function showAssignModal(id, currentTechId) {
    document.getElementById('assignForm').action = '<?= base_url('admin/trouble/assign') ?>/' + id;
    document.getElementById('select_technician').value = currentTechId;
    document.getElementById('assignModal').style.display = 'flex';
}

function closeModals() {
    document.getElementById('addTicketModal').style.display = 'none';
    document.getElementById('assignModal').style.display = 'none';
}

function resolveTicket(id) {
    if (confirm('Tandai laporan #' + id + ' sebagai resolved?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= base_url('admin/trouble/close') ?>/' + id;
        
        // Add CSRF Token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '<?= csrf_token() ?>';
        csrfInput.value = '<?= csrf_hash() ?>';
        form.appendChild(csrfInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?= $this->endSection() ?>
