<?php
/**
 * Hotspot Users View
 */

// Calculate stats from data
$totalUsers = count($users ?? []);
$activeCount = count($active ?? []);
$offlineCount = $totalUsers - $activeCount; // Users not currently connected
$disabledCount = count(array_filter($users ?? [], fn($u) => ($u['disabled'] ?? 'false') === 'true'));
?>
<?= $this->extend('layout') ?>

<?= $this->section('title') ?>Hotspot Users - Gembok Admin<?= $this->endSection() ?>
<?= $this->section('page_title') ?>Hotspot Users<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Stats Row -->
<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card">
        <div class="stat-icon cyan">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <h3><?= $totalUsers ?></h3>
            <p>Total User</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-signal"></i>
        </div>
        <div class="stat-info">
            <h3><?= $activeCount ?></h3>
            <p>Online</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-wifi-slash"></i>
        </div>
        <div class="stat-info">
            <h3><?= $offlineCount ?></h3>
            <p>Offline</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">
            <i class="fas fa-user-slash"></i>
        </div>
        <div class="stat-info">
            <h3><?= $disabledCount ?></h3>
            <p>Disabled</p>
        </div>
    </div>
</div>

<!-- Hotspot Users Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-broadcast-tower"></i> Daftar Hotspot User</h3>
        <div style="display: flex; gap: 0.5rem;">
            <input type="text" id="searchUser" class="form-control" placeholder="Cari user..." style="width: 250px;">
            <button class="btn btn-primary btn-sm" onclick="addUser()">
                <i class="fas fa-plus"></i> Tambah User
            </button>
        </div>
    </div>
    
    <table class="data-table" id="userTable">
        <thead>
            <tr>
                <th>Username</th>
                <th>Profile</th>
                <th>Limit Uptime</th>
                <th>Uptime</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
            <tr>
                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                    <i class="fas fa-broadcast-tower" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                    Belum ada hotspot user atau tidak terkoneksi ke MikroTik
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="width: 32px; height: 32px; background: var(--gradient-primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.8rem;">
                                <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <?= esc($user['name'] ?? 'N/A') ?>
                        </div>
                    </td>
                    <td><span class="badge badge-info"><?= esc($user['profile'] ?? 'default') ?></span></td>
                    <td><?= esc($user['limit-uptime'] ?? '-') ?></td>
                    <td><?= esc($user['uptime'] ?? '0s') ?></td>
                    <td>
                        <span class="badge <?= ($user['disabled'] ?? 'false') === 'true' ? 'badge-danger' : 'badge-success' ?>">
                            <?= ($user['disabled'] ?? 'false') === 'true' ? 'Disabled' : 'Active' ?>
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; gap: 0.25rem;">
                            <button class="btn btn-secondary btn-sm" onclick="editUser('<?= esc($user['name'] ?? '') ?>')" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-secondary btn-sm" onclick="toggleUser('<?= esc($user['name'] ?? '') ?>')" title="Toggle">
                                <i class="fas fa-power-off"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
</table>
</div>

<!-- Add Hotspot User Modal -->
<div id="addModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
    <div class="card" style="width: 450px; max-width: 90%; margin: 2rem;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-plus-circle"></i> Tambah Hotspot User</h3>
            <button onclick="closeModal()" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="addForm" onsubmit="submitAddUser(event)">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" id="add_username" class="form-control" placeholder="Contoh: user001" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="text" name="password" id="add_password" class="form-control" placeholder="Password Hotspot" required>
            </div>
            <div class="form-group">
                <label class="form-label">Profile</label>
                <select name="profile" id="add_profile" class="form-control" required>
                    <option value="default">default</option>
                    <?php foreach ($profiles ?? [] as $profile): ?>
                    <option value="<?= esc($profile['name'] ?? '') ?>"><?= esc($profile['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Limit Uptime (opsional)</label>
                <input type="text" name="limit_uptime" id="add_limit_uptime" class="form-control" placeholder="Contoh: 1d, 1h30m, 30m">
                <small style="color: var(--text-muted);">Format: 1d (1 hari), 1h (1 jam), 30m (30 menit)</small>
            </div>
            <div id="addResult" style="margin-bottom: 1rem; display: none;"></div>
            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Hotspot User Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
    <div class="card" style="width: 450px; max-width: 90%; margin: 2rem;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-edit"></i> Edit Hotspot User</h3>
            <button onclick="closeEditModal()" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="editForm" onsubmit="submitEditUser(event)">
            <input type="hidden" id="edit_original_username">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" id="edit_username" class="form-control" readonly style="opacity: 0.7;">
            </div>
            <div class="form-group">
                <label class="form-label">Password Baru (kosongkan jika tidak ingin ubah)</label>
                <input type="text" id="edit_password" class="form-control" placeholder="Password baru">
            </div>
            <div class="form-group">
                <label class="form-label">Profile</label>
                <select id="edit_profile" class="form-control" required>
                    <option value="default">default</option>
                    <?php foreach ($profiles ?? [] as $profile): ?>
                    <option value="<?= esc($profile['name'] ?? '') ?>"><?= esc($profile['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Limit Uptime</label>
                <input type="text" id="edit_limit_uptime" class="form-control" placeholder="Contoh: 1d, 1h30m, 30m">
                <small style="color: var(--text-muted);">Format: 1d (1 hari), 1h (1 jam), 30m (30 menit)</small>
            </div>
            <div id="editResult" style="margin-bottom: 1rem; display: none;"></div>
            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// User data from PHP
const usersData = <?= json_encode($users ?? []) ?>;

function addUser() {
    document.getElementById('addForm').reset();
    document.getElementById('addResult').style.display = 'none';
    document.getElementById('addModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('addModal').style.display = 'none';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

async function submitAddUser(e) {
    e.preventDefault();
    const resultEl = document.getElementById('addResult');
    resultEl.style.display = 'block';
    resultEl.innerHTML = '<p style="color: var(--neon-cyan);">⏳ Menyimpan ke MikroTik...</p>';
    
    const data = {
        action: 'add_hotspot',
        username: document.getElementById('add_username').value,
        password: document.getElementById('add_password').value,
        profile: document.getElementById('add_profile').value,
        limit_uptime: document.getElementById('add_limit_uptime').value
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

function editUser(username) {
    // Find user data
    const user = usersData.find(u => u.name === username);
    
    // Fill form
    document.getElementById('edit_original_username').value = username;
    document.getElementById('edit_username').value = username;
    document.getElementById('edit_password').value = '';
    document.getElementById('edit_profile').value = user?.profile || 'default';
    document.getElementById('edit_limit_uptime').value = user?.['limit-uptime'] || '';
    document.getElementById('editResult').style.display = 'none';
    
    // Show modal
    document.getElementById('editModal').style.display = 'flex';
}

async function submitEditUser(e) {
    e.preventDefault();
    const resultEl = document.getElementById('editResult');
    resultEl.style.display = 'block';
    resultEl.innerHTML = '<p style="color: var(--neon-cyan);">⏳ Mengupdate ke MikroTik...</p>';
    
    const data = {
        action: 'edit_hotspot',
        username: document.getElementById('edit_original_username').value,
        password: document.getElementById('edit_password').value,
        profile: document.getElementById('edit_profile').value,
        limit_uptime: document.getElementById('edit_limit_uptime').value
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
                closeEditModal();
                window.location.reload();
            }, 1000);
        } else {
            resultEl.innerHTML = '<p style="color: var(--neon-orange);">❌ ' + (result.message || 'Gagal') + '</p>';
        }
    } catch (err) {
        resultEl.innerHTML = '<p style="color: var(--neon-pink);">❌ Error: ' + err.message + '</p>';
    }
}

async function toggleUser(username) {
    if (!confirm('Yakin ingin toggle status ' + username + '?')) return;
    
    try {
        const res = await fetch('<?= base_url('admin/mikrotik/action') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'toggle_hotspot', username: username })
        });
        const result = await res.json();
        alert(result.message || 'Status berhasil diubah');
        window.location.reload();
    } catch (err) {
        alert('Error: ' + err.message);
    }
}

// Search filter
document.getElementById('searchUser')?.addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#userTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(search) ? '' : 'none';
    });
});

// Close modal handlers
document.getElementById('addModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
        closeEditModal();
    }
});
</script>
<?= $this->endSection() ?>


