<?php
/**
 * MikroTik PPPoE Profiles View
 */
$profilesData = $profiles ?? [];
?>
<?= $this->extend('layout') ?>

<?= $this->section('title') ?>Profile PPPoE - Gembok Admin<?= $this->endSection() ?>
<?= $this->section('page_title') ?>Profile PPPoE<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Profiles Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-sliders-h"></i> Daftar Profile PPPoE</h3>
        <button class="btn btn-primary btn-sm" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Tambah Profile
        </button>
    </div>
    
    <table class="data-table" id="profileTable">
        <thead>
            <tr>
                <th>Nama Profile</th>
                <th>Rate Limit</th>
                <th>Local Address</th>
                <th>Remote Address</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($profilesData)): ?>
            <tr>
                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                    <i class="fas fa-sliders-h" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                    Belum ada profile atau tidak terkoneksi ke MikroTik
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($profilesData as $profile): ?>
                <tr>
                    <td>
                        <span class="badge badge-info"><?= esc($profile['name'] ?? 'N/A') ?></span>
                    </td>
                    <td><code style="background: rgba(0, 245, 255, 0.1); padding: 0.25rem 0.5rem; border-radius: 4px; color: var(--neon-cyan);"><?= esc($profile['rate-limit'] ?? 'unlimited') ?></code></td>
                    <td><?= esc($profile['local-address'] ?? '-') ?></td>
                    <td><?= esc($profile['remote-address'] ?? '-') ?></td>
                    <td>
                        <div style="display: flex; gap: 0.25rem;">
                            <button class="btn btn-secondary btn-sm" onclick="openEditModal('<?= esc($profile['name'] ?? '') ?>')" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-secondary btn-sm" onclick="deleteProfile('<?= esc($profile['name'] ?? '') ?>')" title="Delete">
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

<!-- Add/Edit Profile Modal -->
<div id="profileModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
    <div class="card" style="width: 500px; max-width: 90%; margin: 2rem;">
        <div class="card-header">
            <h3 class="card-title" id="modalTitle"><i class="fas fa-plus-circle"></i> Tambah Profile PPPoE</h3>
            <button onclick="closeModal()" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="profileForm" onsubmit="submitProfile(event)">
            <input type="hidden" id="edit_mode" value="add">
            <input type="hidden" id="original_name">
            <div class="form-group">
                <label class="form-label">Nama Profile</label>
                <input type="text" id="profile_name" class="form-control" placeholder="Contoh: up-10Mbps" required>
            </div>
            <div class="form-group">
                <label class="form-label">Rate Limit (Upload/Download)</label>
                <input type="text" id="rate_limit" class="form-control" placeholder="Contoh: 10M/10M atau 5M/20M">
                <small style="color: var(--text-muted);">Format: upload/download (K=kbps, M=Mbps). Kosongkan untuk unlimited.</small>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Local Address</label>
                    <input type="text" id="local_address" class="form-control" placeholder="Pool atau IP">
                </div>
                <div class="form-group">
                    <label class="form-label">Remote Address</label>
                    <input type="text" id="remote_address" class="form-control" placeholder="Pool atau IP">
                </div>
            </div>
            <div id="resultMsg" style="margin-bottom: 1rem; display: none;"></div>
            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Profile data from PHP
const profilesData = <?= json_encode($profilesData) ?>;

function openAddModal() {
    document.getElementById('edit_mode').value = 'add';
    document.getElementById('original_name').value = '';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Tambah Profile PPPoE';
    document.getElementById('profileForm').reset();
    document.getElementById('resultMsg').style.display = 'none';
    document.getElementById('profileModal').style.display = 'flex';
}

function openEditModal(name) {
    const profile = profilesData.find(p => p.name === name);
    if (!profile) {
        alert('Profile tidak ditemukan!');
        return;
    }
    
    document.getElementById('edit_mode').value = 'edit';
    document.getElementById('original_name').value = name;
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Profile PPPoE';
    document.getElementById('profile_name').value = profile.name || '';
    document.getElementById('rate_limit').value = profile['rate-limit'] || '';
    document.getElementById('local_address').value = profile['local-address'] || '';
    document.getElementById('remote_address').value = profile['remote-address'] || '';
    document.getElementById('resultMsg').style.display = 'none';
    document.getElementById('profileModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('profileModal').style.display = 'none';
}

async function submitProfile(e) {
    e.preventDefault();
    const resultEl = document.getElementById('resultMsg');
    resultEl.style.display = 'block';
    resultEl.innerHTML = '<p style="color: var(--neon-cyan);">⏳ Menyimpan ke MikroTik...</p>';
    
    const mode = document.getElementById('edit_mode').value;
    const data = {
        action: mode === 'edit' ? 'edit_pppoe_profile' : 'add_pppoe_profile',
        original_name: document.getElementById('original_name').value,
        name: document.getElementById('profile_name').value,
        rate_limit: document.getElementById('rate_limit').value,
        local_address: document.getElementById('local_address').value,
        remote_address: document.getElementById('remote_address').value
    };
    
    try {
        const res = await fetch('<?= base_url('admin/mikrotik/action') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        
        if (result.success) {
            resultEl.innerHTML = '<p style="color: var(--neon-green);">✅ ' + result.message + '</p>';
            setTimeout(() => {
                closeModal();
                window.location.reload();
            }, 1000);
        } else {
            resultEl.innerHTML = '<p style="color: var(--neon-orange);">❌ ' + (result.message || 'Gagal') + '</p>';
        }
    } catch (err) {
        resultEl.innerHTML = '<p style="color: var(--neon-pink);">❌ Error: ' + err.message + '</p>';
    }
}

function deleteProfile(name) {
    if (!confirm('Yakin ingin hapus profile "' + name + '"?\n\nUser yang menggunakan profile ini akan terpengaruh!')) return;
    
    fetch('<?= base_url('admin/mikrotik/action') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete_pppoe_profile', name: name })
    })
    .then(res => res.json())
    .then(result => {
        alert(result.message || 'Profile dihapus');
        if (result.success) window.location.reload();
    })
    .catch(err => alert('Error: ' + err.message));
}

// Close modal handlers
document.getElementById('profileModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});
</script>
<?= $this->endSection() ?>
