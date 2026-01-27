<?= $this->extend('layout') ?>

<?= $this->section('title') ?>Manajemen Teknisi - Gembok Admin<?= $this->endSection() ?>
<?= $this->section('page_title') ?>Manajemen Teknisi<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon cyan">
            <i class="fas fa-user-shield"></i>
        </div>
        <div class="stat-info">
            <h3><?= count($technicians ?? []) ?></h3>
            <p>Total Teknisi</p>
        </div>
    </div>
</div>

<!-- Add Technician Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-plus-circle"></i> Tambah Teknisi Baru</h3>
    </div>
    <form action="<?= base_url('admin/technicians/add') ?>" method="POST">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-top: 1rem;">
            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="name" class="form-control" required placeholder="Nama Teknisi">
            </div>
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required placeholder="Username untuk login">
            </div>
            <div class="form-group">
                <label class="form-label">No. WhatsApp</label>
                <input type="text" name="phone" class="form-control" required placeholder="Contoh: 08123456789">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required placeholder="Password">
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
            <i class="fas fa-save"></i> Simpan Teknisi
        </button>
    </form>
</div>

<!-- Technician List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> Daftar Teknisi</h3>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>WhatsApp</th>
                    <th>Status</th>
                    <th>Dibuat Pada</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($technicians as $t): ?>
                <tr>
                    <td>#<?= $t['id'] ?></td>
                    <td><strong><?= esc($t['name']) ?></strong></td>
                    <td><code><?= esc($t['username'] ?? '-') ?></code></td>
                    <td><?= esc($t['phone'] ?? '-') ?></td>
                    <td>
                        <?php if ($t['is_active']): ?>
                            <span class="badge badge-success">Aktif</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Nonaktif</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d M Y', strtotime($t['created_at'])) ?></td>
                    <td>
                        <button class="btn btn-secondary btn-sm" onclick="editTechnician(<?= esc(json_encode($t)) ?>)"><i class="fas fa-edit"></i></button>
                        <a href="<?= base_url('admin/technicians/delete/' . $t['id']) ?>" class="btn btn-secondary btn-sm" style="color: var(--neon-pink);" onclick="return confirm('Apakah Anda yakin ingin menghapus teknisi ini?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($technicians)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">Belum ada data teknisi.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Technician Modal -->
<div id="editModal" class="modal-overlay" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Edit Teknisi</h3>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form id="editForm" method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" id="edit_username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">No. WhatsApp</label>
                    <input type="text" name="phone" id="edit_phone" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password Baru (Kosongkan jika tidak ganti)</label>
                    <input type="password" name="password" class="form-control" placeholder="Password baru">
                </div>
                <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem;">
                    <input type="checkbox" name="is_active" id="edit_active" value="1">
                    <label for="edit_active" style="margin-bottom: 0;">Akun Aktif</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<style>
    .modal-overlay { position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); display:flex; align-items:center; justify-content:center; z-index:9999; }
    .modal { background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 12px; width: 90%; max-width: 500px; padding: 0; overflow: hidden; }
    .modal-header { padding: 1rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
    .modal-body { padding: 1.5rem; }
    .modal-footer { padding: 1rem; border-top: 1px solid var(--border-color); display:flex; justify-content: flex-end; gap: 0.5rem; background: rgba(0,0,0,0.1); }
    .modal-close { background:none; border:none; font-size: 1.5rem; color: var(--text-muted); cursor:pointer; }
    .form-group { margin-bottom: 1rem; }
    .form-label { display: block; margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-secondary); }
</style>

<script>
    function editTechnician(data) {
        document.getElementById('editForm').action = '<?= base_url('admin/technicians/update') ?>/' + data.id;
        document.getElementById('edit_name').value = data.name;
        document.getElementById('edit_username').value = data.username;
        document.getElementById('edit_phone').value = data.phone || '';
        document.getElementById('edit_active').checked = data.is_active == 1;
        document.getElementById('editModal').style.display = 'flex';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }
</script>
<?= $this->endSection() ?>
