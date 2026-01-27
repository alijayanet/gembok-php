<?= $this->extend('layout') ?>

<?= $this->section('title') ?>Dashboard Teknisi - Gembok Admin<?= $this->endSection() ?>
<?= $this->section('page_title') ?>Tugas Saya (Teknisi)<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Assigned Tickets -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tools"></i> Tiket Gangguan Ditugaskan</h3>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tiket</th>
                    <th>Pelanggan</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $ticket): ?>
                <tr>
                    <td>
                        <strong>#<?= $ticket['id'] ?>: <?= esc($ticket['description']) ?></strong>
                        <br>
                        <small class="text-muted"><i class="fas fa-clock"></i> <?= date('d M Y H:i', strtotime($ticket['created_at'])) ?></small>
                        
                        <?php if (!empty($ticket['pppoe_username'])): ?>
                        <div id="onu-info-<?= $ticket['id'] ?>" class="onu-quick-info" style="margin-top: 5px; font-size: 0.85rem;">
                            <span class="text-muted">PPPoE: <?= esc($ticket['pppoe_username']) ?></span>
                            <button class="btn btn-xs btn-outline-cyan" onclick="checkOnu(<?= $ticket['id'] ?>, '<?= esc($ticket['pppoe_username']) ?>')" style="padding: 0 5px; font-size: 0.7rem;">
                                <i class="fas fa-satellite-dish"></i> Cek Redaman
                            </button>
                            <div class="onu-result" style="display: none; margin-top: 3px;"></div>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= esc($ticket['customer_name']) ?>
                        <br>
                        <small><i class="fab fa-whatsapp"></i> <?= esc($ticket['customer_phone']) ?></small>
                        <br>
                        <small><i class="fas fa-map-marker-alt"></i> <?= esc($ticket['customer_address']) ?></small>
                    </td>
                    <td>
                        <?php if ($ticket['priority'] === 'high'): ?>
                            <span class="badge badge-danger">High</span>
                        <?php elseif ($ticket['priority'] === 'medium'): ?>
                            <span class="badge badge-warning">Medium</span>
                        <?php else: ?>
                            <span class="badge badge-info">Low</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <select onchange="updateTicketStatus(<?= $ticket['id'] ?>, this.value)" class="form-control btn-sm" style="width: auto; display: inline-block;">
                            <option value="pending" <?= $ticket['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="resolved" <?= $ticket['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                            <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                    </td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <button class="btn btn-primary btn-sm" onclick="openDetails(<?= $ticket['id'] ?>)" title="Catatan Penyelesaian">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if (!empty($ticket['pppoe_username'])): ?>
                            <a href="<?= base_url('admin/technician/genieacs?search=' . esc($ticket['pppoe_username'])) ?>" class="btn btn-secondary btn-sm" title="Buka GenieACS">
                                <i class="fas fa-server"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($tickets)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">Tidak ada tiket yang ditugaskan kepada Anda.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Update -->
<div id="updateModal" class="modal-overlay" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3>Update Penyelesaian</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="modal_ticket_id">
            <div class="form-group">
                <label class="form-label">Catatan Penyelesaian / Progress</label>
                <textarea id="modal_notes" class="form-control" rows="5" placeholder="Tulis apa yang sudah dikerjakan..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal()">Batal</button>
            <button class="btn btn-primary" onclick="saveUpdate()">Simpan</button>
        </div>
    </div>
</div>

<style>
    .modal-overlay { position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); display:flex; align-items:center; justify-content:center; z-index:9999; }
    .modal { background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 12px; width: 90%; max-width: 500px; }
    .modal-header { padding: 1rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; }
    .modal-body { padding: 1rem; }
    .modal-footer { padding: 1rem; border-top: 1px solid var(--border-color); display:flex; justify-content: flex-end; gap: 0.5rem; }
    .modal-close { background:none; border:none; font-size: 1.5rem; color: var(--text-muted); cursor:pointer; }
</style>

<script>
    async function updateTicketStatus(id, status) {
        if (status === 'resolved' || status === 'in_progress') {
            document.getElementById('modal_ticket_id').value = id;
            document.getElementById('updateModal').setAttribute('data-status', status);
            document.getElementById('updateModal').style.display = 'flex';
        } else {
            // direct update
            await callUpdateStatus(id, status, '');
        }
    }

    function closeModal() {
        document.getElementById('updateModal').style.display = 'none';
    }

    async function saveUpdate() {
        const id = document.getElementById('modal_ticket_id').value;
        const status = document.getElementById('updateModal').getAttribute('data-status');
        const notes = document.getElementById('modal_notes').value;
        
        await callUpdateStatus(id, status, notes);
        closeModal();
    }

    async function callUpdateStatus(id, status, notes) {
        try {
            const response = await fetch('<?= base_url('admin/technician/updateStatus') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: `id=${id}&status=${status}&notes=${encodeURIComponent(notes)}`
            });
            const res = await response.json();
            if (res.success) {
                location.reload();
            } else {
                alert('Gagal update: ' + res.message);
            }
        } catch (e) {
            alert('Error: ' + e.message);
        }
    }

    async function checkOnu(ticketId, pppoe) {
        const row = document.getElementById(`onu-info-${ticketId}`);
        const resultDiv = row.querySelector('.onu-result');
        const btn = row.querySelector('button');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        resultDiv.style.display = 'block';
        resultDiv.innerHTML = '<span class="text-muted">⏳ Menghubungi GenieACS...</span>';

        try {
            const response = await fetch(`<?= base_url('admin/technician/getOnuData') ?>?pppoe=${encodeURIComponent(pppoe)}`);
            const data = await response.json();
            
            if (data.success) {
                const statusColor = data.online ? 'var(--neon-green)' : 'var(--neon-pink)';
                const rxColor = parseFloat(data.rxPower) < -25 ? 'var(--neon-pink)' : 'var(--neon-green)';
                
                resultDiv.innerHTML = `
                    <div style="display:flex; gap: 10px; flex-wrap: wrap;">
                        <span>Status: <b style="color: ${statusColor}">${data.online ? 'ONLINE' : 'OFFLINE'}</b></span>
                        <span>RX: <b style="color: ${rxColor}">${data.rxPower} dBm</b></span>
                        <span>SSID: <b style="color: var(--neon-cyan)">${data.ssid}</b></span>
                    </div>
                `;
            } else {
                resultDiv.innerHTML = `<span style="color: var(--neon-pink);">❌ ${data.message}</span>`;
            }
        } catch (e) {
            resultDiv.innerHTML = `<span style="color: var(--neon-pink);">❌ Error: ${e.message}</span>`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-satellite-dish"></i> Cek Lagi';
        }
    }

    function openDetails(id) {
        document.getElementById('modal_ticket_id').value = id;
        document.getElementById('updateModal').setAttribute('data-status', 'in_progress'); // Default to in_progress when editing details
        document.getElementById('updateModal').style.display = 'flex';
    }
</script>

<style>
    .btn-xs { padding: 1px 5px; font-size: 0.75rem; border-radius: 3px; }
    .btn-outline-cyan { color: var(--neon-cyan); border: 1px solid var(--neon-cyan); background: transparent; }
    .btn-outline-cyan:hover { background: var(--neon-cyan); color: var(--bg-primary); }
    .onu-result { background: rgba(0,0,0,0.2); padding: 5px; border-radius: 4px; border-left: 3px solid var(--neon-cyan); }
</style>
<?= $this->endSection() ?>
