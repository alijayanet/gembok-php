<?php
/**
 * Voucher Generation View
 */
$profilesData = $profiles ?? [];
$vouchersData = $vouchers ?? [];
?>
<?= $this->extend('layout') ?>

<?= $this->section('title') ?>Voucher Generator - Gembok Admin<?= $this->endSection() ?>
<?= $this->section('page_title') ?>Voucher Generator<?= $this->endSection() ?>

<?= $this->section('head') ?>
<style>
    @media print {
        body * { visibility: hidden; }
        #printArea, #printArea * { visibility: visible; }
        #printArea { position: absolute; left: 0; top: 0; width: 100%; }
        .voucher-card { 
            page-break-inside: avoid; 
            border: 2px dashed #000 !important;
            background: #fff !important;
            color: #000 !important;
            padding: 15px !important;
            margin: 5px !important;
        }
        .voucher-card * { color: #000 !important; }
    }
    .voucher-card {
        background: linear-gradient(135deg, rgba(0,245,255,0.1) 0%, rgba(147,51,255,0.1) 100%);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1rem;
        text-align: center;
        transition: transform 0.2s;
    }
    .voucher-card:hover { transform: scale(1.02); }
    .voucher-code {
        font-family: 'Courier New', monospace;
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--neon-cyan);
        letter-spacing: 2px;
        margin: 0.5rem 0;
    }
    .voucher-profile { font-size: 0.75rem; color: var(--text-muted); }
    .voucher-pass { font-size: 0.9rem; color: var(--text-secondary); }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div style="display: grid; grid-template-columns: 350px 1fr; gap: 1.5rem;">
    <!-- Generate Form -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-ticket-alt"></i> Generate Voucher</h3>
        </div>
        
        <form id="generateForm">
            <div class="form-group">
                <label class="form-label">Profile Hotspot</label>
                <select id="voucherProfile" class="form-control" required>
                    <option value="">Pilih Profile</option>
                    <?php foreach ($profilesData as $profile): ?>
                    <option value="<?= esc($profile['name'] ?? '') ?>"><?= esc($profile['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Jumlah Voucher</label>
                <input type="number" id="voucherQty" class="form-control" min="1" max="100" value="10" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Prefix Username</label>
                <input type="text" id="voucherPrefix" class="form-control" placeholder="VCH-" value="VCH-">
            </div>
            
            <div class="form-group">
                <label class="form-label">Panjang Karakter</label>
                <input type="number" id="voucherLength" class="form-control" min="4" max="12" value="6" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <input type="checkbox" id="saveToMikrotik" checked> Simpan ke MikroTik
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;" id="generateBtn">
                <i class="fas fa-magic"></i> Generate Voucher
            </button>
        </form>
    </div>
    
    <!-- Generated Vouchers -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list"></i> Voucher yang Digenerate</h3>
            <div style="display: flex; gap: 0.5rem;">
                <button class="btn btn-secondary btn-sm" onclick="printVouchers()" id="printBtn" disabled>
                    <i class="fas fa-print"></i> Print
                </button>
                <button class="btn btn-secondary btn-sm" onclick="exportCsv()" id="exportBtn" disabled>
                    <i class="fas fa-download"></i> CSV
                </button>
            </div>
        </div>
        
        <div id="voucherList" style="min-height: 300px;">
            <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                <i class="fas fa-ticket-alt" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <p>Belum ada voucher digenerate</p>
                <p style="font-size: 0.85rem;">Isi form di samping dan klik Generate</p>
            </div>
        </div>
    </div>
</div>

<!-- Print Area (Hidden) -->
<div id="printArea" style="display: none;"></div>

<!-- Existing Vouchers Table -->
<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-database"></i> Hotspot Users (dari MikroTik)</h3>
        <input type="text" id="searchVoucher" class="form-control" placeholder="Cari..." style="width: 200px; padding: 0.4rem; margin-left: auto;">
    </div>
    
    <div style="overflow-x: auto;">
        <table class="data-table" id="voucherTable">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Profile</th>
                    <th>Limit Uptime</th>
                    <th>Comment</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vouchersData)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                        <i class="fas fa-wifi" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                        Tidak ada data hotspot user atau tidak terkoneksi ke MikroTik
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($vouchersData as $v): ?>
                    <tr>
                        <td><code style="color: var(--neon-cyan);"><?= esc($v['name'] ?? '') ?></code></td>
                        <td><code><?= esc($v['password'] ?? '***') ?></code></td>
                        <td><span class="badge badge-info"><?= esc($v['profile'] ?? 'default') ?></span></td>
                        <td><?= esc($v['limit-uptime'] ?? '-') ?></td>
                        <td><?= esc($v['comment'] ?? '-') ?></td>
                        <td>
                            <?php if (($v['disabled'] ?? 'false') === 'true'): ?>
                                <span class="badge badge-warning">Disabled</span>
                            <?php else: ?>
                                <span class="badge badge-success">Active</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-secondary btn-sm" onclick="deleteVoucher('<?= esc($v['name'] ?? '') ?>')" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
let generatedVouchers = [];

document.getElementById('generateForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const profile = document.getElementById('voucherProfile').value;
    const qty = parseInt(document.getElementById('voucherQty').value);
    const prefix = document.getElementById('voucherPrefix').value;
    const length = parseInt(document.getElementById('voucherLength').value);
    const saveToMikrotik = document.getElementById('saveToMikrotik').checked;
    
    if (!profile) {
        alert('Pilih profile terlebih dahulu');
        return;
    }
    
    const btn = document.getElementById('generateBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
    
    // Generate random codes
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    generatedVouchers = [];
    
    for (let i = 0; i < qty; i++) {
        let code = '';
        for (let j = 0; j < length; j++) {
            code += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        generatedVouchers.push({
            username: prefix + code,
            password: code,
            profile: profile
        });
    }
    
    // If save to MikroTik is checked, send to backend
    if (saveToMikrotik) {
        try {
            const res = await fetch('<?= base_url('admin/mikrotik/action') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'generate_vouchers',
                    vouchers: generatedVouchers
                })
            });
            const result = await res.json();
            
            if (!result.success) {
                alert('Perhatian: ' + result.message);
            }
        } catch (err) {
            console.log('MikroTik save error:', err);
        }
    }
    
    // Display vouchers
    displayVouchers();
    
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-magic"></i> Generate Voucher';
    
    // Enable print/export buttons
    document.getElementById('printBtn').disabled = false;
    document.getElementById('exportBtn').disabled = false;
});

function displayVouchers() {
    const listEl = document.getElementById('voucherList');
    listEl.innerHTML = `
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; padding: 1rem;">
            ${generatedVouchers.map(v => `
                <div class="voucher-card">
                    <div class="voucher-profile">${v.profile}</div>
                    <div class="voucher-code">${v.username}</div>
                    <div class="voucher-pass">Pass: ${v.password}</div>
                </div>
            `).join('')}
        </div>
    `;
    
    // Prepare print area
    const printArea = document.getElementById('printArea');
    printArea.innerHTML = `
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; padding: 10px;">
            ${generatedVouchers.map(v => `
                <div class="voucher-card" style="border: 2px dashed #ccc; padding: 15px; text-align: center;">
                    <div style="font-size: 10px; color: #666;">${v.profile}</div>
                    <div style="font-size: 16px; font-weight: bold; margin: 5px 0;">${v.username}</div>
                    <div style="font-size: 12px;">Pass: ${v.password}</div>
                </div>
            `).join('')}
        </div>
    `;
}

function printVouchers() {
    if (generatedVouchers.length === 0) {
        alert('Tidak ada voucher untuk diprint');
        return;
    }
    
    const printArea = document.getElementById('printArea');
    printArea.style.display = 'block';
    window.print();
    setTimeout(() => { printArea.style.display = 'none'; }, 100);
}

function exportCsv() {
    if (generatedVouchers.length === 0) {
        alert('Tidak ada voucher untuk diexport');
        return;
    }
    
    let csv = 'Username,Password,Profile\n';
    generatedVouchers.forEach(v => {
        csv += `${v.username},${v.password},${v.profile}\n`;
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'vouchers_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

async function deleteVoucher(username) {
    if (!confirm('Yakin ingin hapus voucher "' + username + '"?')) return;
    
    try {
        const res = await fetch('<?= base_url('admin/mikrotik/action') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete_hotspot_user', username: username })
        });
        const result = await res.json();
        alert(result.message);
        if (result.success) window.location.reload();
    } catch (err) {
        alert('Error: ' + err.message);
    }
}

// Search filter
document.getElementById('searchVoucher')?.addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#voucherTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(search) ? '' : 'none';
    });
});
</script>
<?= $this->endSection() ?>
