<?php
/**
 * Packages View - Manage Internet Packages
 */
?>
<?= $this->extend('layout') ?>

<?= $this->section('title') ?>Paket Internet - Gembok Admin<?= $this->endSection() ?>
<?= $this->section('page_title') ?>Paket Internet<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger" style="background: rgba(255, 107, 53, 0.1); border: 1px solid #ff6b35; color: #ff6b35; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
    <i class="fas fa-exclamation-triangle"></i> <?= session()->getFlashdata('error') ?>
</div>
<?php endif; ?>

<!-- Stats Row -->
<div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
    <div class="stat-card">
        <div class="stat-icon cyan">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-info">
            <h3><?= count($packages ?? []) ?></h3>
            <p>Total Paket</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <h3>0</h3>
            <p>Pelanggan Aktif</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="stat-info">
            <h3>Rp 0</h3>
            <p>Total Pendapatan/Bulan</p>
        </div>
    </div>
</div>

<!-- Add Package Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-plus-circle"></i> Tambah Paket Baru</h3>
        <?php if (isset($mikrotik_connected)): ?>
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <?php if ($mikrotik_connected): ?>
            <span class="badge badge-success"><i class="fas fa-link"></i> MikroTik Terhubung</span>
            <?php else: ?>
            <span class="badge badge-warning"><i class="fas fa-unlink"></i> MikroTik Tidak Terhubung (Data Dummy)</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <form id="addPackageForm" method="POST" action="<?= base_url('admin/billing/packages/add') ?>">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Nama Paket</label>
                <input type="text" name="name" class="form-control" placeholder="Misal: Paket 10 Mbps" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Harga per Bulan</label>
                <input type="number" name="price" class="form-control" placeholder="Misal: 250000" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Profile MikroTik (Normal) <i class="fas fa-network-wired" style="color: var(--neon-green);"></i></label>
                <select name="profile_normal" class="form-control" required>
                    <option value="">-- Pilih Profile Normal --</option>
                    <?php foreach ($mikrotik_profiles ?? [] as $profile): ?>
                    <option value="<?= esc($profile['name'] ?? '') ?>"><?= esc($profile['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
                <small style="color: var(--text-muted);">Profile yang digunakan saat pelanggan aktif/membayar</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Profile MikroTik (Isolir) <i class="fas fa-ban" style="color: var(--neon-orange);"></i></label>
                <select name="profile_isolir" class="form-control" required>
                    <option value="">-- Pilih Profile Isolir --</option>
                    <?php foreach ($mikrotik_profiles ?? [] as $profile): ?>
                    <option value="<?= esc($profile['name'] ?? '') ?>"><?= esc($profile['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
                <small style="color: var(--text-muted);">Profile yang digunakan saat pelanggan belum bayar/terisolir</small>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Keterangan</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Keterangan tambahan (opsional)"></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Simpan Paket
        </button>
    </form>
</div>

<!-- Packages List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> Daftar Paket</h3>
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Nama Paket</th>
                <th>Harga</th>
                <th>Profile Normal</th>
                <th>Profile Isolir</th>
                <th>Pelanggan</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($packages)): ?>
            <tr>
                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                    <i class="fas fa-box" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                    Belum ada paket terdaftar
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($packages as $package): ?>
                <tr>
                    <td>
                        <strong><?= esc($package['name']) ?></strong>
                        <?php if ($package['description']): ?>
                        <br><small style="color: var(--text-muted);"><?= esc($package['description']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong style="color: var(--neon-green);">Rp <?= number_format($package['price'], 0, ',', '.') ?></strong>
                    </td>
                    <td><span class="badge badge-success"><?= esc($package['profile_normal']) ?></span></td>
                    <td><span class="badge badge-warning"><?= esc($package['profile_isolir']) ?></span></td>
                    <td><?= $package['customer_count'] ?? 0 ?> pelanggan</td>
                    <td>
                        <div style="display: flex; gap: 0.25rem;">
                            <button class="btn btn-secondary btn-sm" onclick="editPackage(<?= $package['id'] ?>)" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-secondary btn-sm" onclick="deletePackage(<?= $package['id'] ?>)" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Edit Package Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
    <div class="card" style="width: 500px; max-width: 90%; margin: 2rem;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-edit"></i> Edit Paket</h3>
            <button onclick="closeEditModal()" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="editForm" method="POST" action="">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group">
                <label class="form-label">Nama Paket</label>
                <input type="text" name="name" id="edit_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Harga per Bulan</label>
                <input type="number" name="price" id="edit_price" class="form-control" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Profile Normal</label>
                    <select name="profile_normal" id="edit_profile_normal" class="form-control" required>
                        <?php foreach ($mikrotik_profiles ?? [] as $profile): ?>
                        <option value="<?= esc($profile['name'] ?? '') ?>"><?= esc($profile['name'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Profile Isolir</label>
                    <select name="profile_isolir" id="edit_profile_isolir" class="form-control" required>
                        <?php foreach ($mikrotik_profiles ?? [] as $profile): ?>
                        <option value="<?= esc($profile['name'] ?? '') ?>"><?= esc($profile['name'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Keterangan</label>
                <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
            </div>
            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Package data from PHP
const packagesData = <?= json_encode($packages ?? []) ?>;

function editPackage(id) {
    const pkg = packagesData.find(p => p.id == id);
    if (!pkg) {
        alert('Paket tidak ditemukan!');
        return;
    }
    
    // Fill form
    document.getElementById('edit_id').value = pkg.id;
    document.getElementById('edit_name').value = pkg.name || '';
    document.getElementById('edit_price').value = pkg.price || '';
    document.getElementById('edit_profile_normal').value = pkg.profile_normal || '';
    document.getElementById('edit_profile_isolir').value = pkg.profile_isolir || '';
    document.getElementById('edit_description').value = pkg.description || '';
    
    // Set form action
    document.getElementById('editForm').action = '<?= base_url('admin/billing/packages/update/') ?>' + id;
    
    // Show modal
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function deletePackage(id) {
    const pkg = packagesData.find(p => p.id == id);
    const name = pkg ? pkg.name : 'Paket #' + id;
    
    if (confirm('Yakin ingin menghapus paket "' + name + '"?\n\nPelanggan yang menggunakan paket ini akan terpengaruh!')) {
        window.location.href = '<?= base_url('admin/billing/packages/delete/') ?>' + id;
    }
}

// Close modal on backdrop click
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEditModal();
    }
});
</script>
<?= $this->endSection() ?>

