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
                        <button class="btn btn-secondary btn-sm"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-secondary btn-sm" style="color: var(--neon-pink);"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($technicians)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">Belum ada data teknisi.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
