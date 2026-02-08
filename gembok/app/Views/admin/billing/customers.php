<?php
/**
 * Customers View - Manage Subscribers
 */
?>
<?= $this->extend('layout') ?>

<?= $this->section('title') ?>Pelanggan - Gembok Admin<?= $this->endSection() ?>
<?= $this->section('page_title') ?>Data Pelanggan<?= $this->endSection() ?>

<?= $this->section('head') ?>
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #map-picker {
        height: 350px;
        width: 100%;
        border-radius: 8px;
        margin-top: 10px;
        z-index: 1;
        position: relative;
    }
    .isolate-badge {
        background: rgba(255, 107, 53, 0.2); 
        color: #ff6b35; 
        padding: 2px 8px; 
        border-radius: 4px; 
        font-size: 0.8em;
        border: 1px solid rgba(255, 107, 53, 0.3);
    }
    .map-controls {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 1000;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .map-btn {
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        padding: 8px 12px;
        border-radius: 8px;
        color: var(--text-primary);
        cursor: pointer;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }
    .map-btn:hover {
        background: rgba(0, 245, 255, 0.1);
        border-color: var(--neon-cyan);
    }
    .map-btn.active {
        background: var(--neon-cyan);
        color: var(--bg-primary);
    }
    .coord-display {
        display: flex;
        gap: 1rem;
        margin-bottom: 0.5rem;
    }
    .coord-display input {
        background: rgba(0, 245, 255, 0.1);
        border: 1px solid var(--neon-cyan);
    }
    .map-wrapper {
        position: relative;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Stats Grid -->
<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card">
        <div class="stat-icon cyan">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <h3><?= count($customers ?? []) ?></h3>
            <p>Total Pelanggan</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
            <h3><?= count(array_filter($customers ?? [], fn($c) => $c['status'] === 'active')) ?></h3>
            <p>Aktif</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">
            <i class="fas fa-ban"></i>
        </div>
        <div class="stat-info">
            <h3><?= count(array_filter($customers ?? [], fn($c) => $c['status'] === 'isolated')) ?></h3>
            <p>Terisolir</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="fas fa-wallet"></i>
        </div>
        <div class="stat-info">
            <?php 
            $totalRevenue = 0;
            foreach ($customers ?? [] as $c) {
                if ($c['status'] === 'active') {
                    $totalRevenue += $c['package_price'] ?? 0;
                }
            }
            ?>
            <h3>Rp <?= number_format($totalRevenue, 0, ',', '.') ?></h3>
            <p>Estimasi Pendapatan</p>
        </div>
    </div>
</div>

<!-- Add Customer Form -->
<div class="card">
    <div class="card-header" style="cursor: pointer;" onclick="toggleAddForm()">
        <h3 class="card-title"><i class="fas fa-user-plus"></i> Tambah Pelanggan Baru <small style="font-size: 0.8em; color: var(--text-muted);">(Klik untuk buka/tutup)</small></h3>
        <i class="fas fa-chevron-down" style="margin-left: auto;"></i>
    </div>
    
    <div id="addCustomerFormWrapper" class="hidden">
        <form id="addCustomerForm" method="POST" action="<?= base_url('admin/billing/customers/add') ?>" style="padding-top: 1rem;">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Nama Pelanggan</label>
                    <input type="text" name="name" class="form-control" required placeholder="Nama Lengkap">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nomor HP (WhatsApp)</label>
                    <input type="text" name="phone" class="form-control" required placeholder="08xxxxxxxxxx">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Username PPPoE</label>
                    <input type="text" name="pppoe_username" class="form-control" required placeholder="Username di MikroTik">
                    <small style="color: var(--text-muted);"><i class="fas fa-info-circle"></i> Password PPPoE akan sama dengan username</small>
                </div>
                
                <!-- ✅ CHECKBOX CREATE PPPOE -->
                <div class="form-group">
                    <div style="display: flex; align-items: start; gap: 10px; padding: 10px; background: rgba(0, 245, 255, 0.05); border: 1px solid var(--border-color); border-radius: 8px;">
                        <input type="checkbox" 
                               name="create_pppoe" 
                               id="create_pppoe" 
                               value="1" 
                               checked 
                               style="width: 18px; height: 18px; margin-top: 2px; cursor: pointer;">
                        <label for="create_pppoe" style="margin: 0; cursor: pointer; flex: 1;">
                            <strong style="color: var(--neon-cyan);"><i class="fas fa-network-wired"></i> Create PPPoE User di MikroTik</strong>
                            <br>
                            <small style="color: var(--text-muted);">
                                Uncheck jika PPPoE user sudah ada di MikroTik (untuk import data existing)
                            </small>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Paket Langganan</label>
                    <select name="package_id" class="form-control" required>
                        <option value="">Pilih Paket</option>
                        <?php foreach ($packages ?? [] as $pkg): ?>
                        <option value="<?= $pkg['id'] ?>">
                            <?= esc($pkg['name']) ?> (Rp <?= number_format($pkg['price'], 0, ',', '.') ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tanggal Isolir (Tgl Jatuh Tempo)</label>
                    <input type="number" name="isolation_date" class="form-control" value="20" min="1" max="28" required>
                    <small style="color: var(--text-muted);">Tanggal otomatis terisolir jika belum bayar</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Alamat Lengkap</label>
                    <textarea name="address" class="form-control" rows="1" placeholder="Alamat rumah"></textarea>
                </div>
            </div>

            <div class="form-group" style="margin-top: 1rem;">
                <label class="form-label"><i class="fas fa-map-marker-alt" style="color: var(--neon-cyan);"></i> Lokasi Rumah Pelanggan</label>
                <div class="coord-display">
                    <input type="text" name="lat" id="lat" class="form-control" placeholder="Latitude" readonly>
                    <input type="text" name="lng" id="lng" class="form-control" placeholder="Longitude" readonly>
                </div>
                
                <div class="map-wrapper">
                    <div id="map-picker"></div>
                    <div class="map-controls">
                        <button type="button" class="map-btn" onclick="getMyLocation()" title="Gunakan lokasi saya">
                            <i class="fas fa-crosshairs"></i> Lokasi Saya
                        </button>
                        <button type="button" class="map-btn" id="layerToggle" onclick="toggleMapLayer()" title="Ganti layer peta">
                            <i class="fas fa-layer-group"></i> Satellite
                        </button>
                    </div>
                </div>
                <small style="color: var(--text-muted); margin-top: 0.5rem; display: block;">
                    <i class="fas fa-info-circle"></i> Klik pada peta untuk menandai lokasi rumah pelanggan
                </small>
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-top: 1rem; width: 100%;">
                <i class="fas fa-save"></i> Simpan Data Pelanggan
            </button>
        </form>
    </div>
</div>

<!-- Customer List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-users"></i> Daftar Pelanggan</h3>
        <div style="margin-left: auto; display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <!-- Export/Import Buttons -->
            <div class="btn-group" style="display: flex; gap: 0.25rem;">
                <a href="<?= base_url('admin/billing/customers/export') ?>" class="btn btn-secondary btn-sm" title="Export ke Excel">
                    <i class="fas fa-file-export"></i> Export
                </a>
                <a href="<?= base_url('admin/billing/customers/template') ?>" class="btn btn-secondary btn-sm" title="Download Template Import">
                    <i class="fas fa-file-download"></i> Template
                </a>
                <button type="button" class="btn btn-primary btn-sm" onclick="openImportModal()" title="Import dari Excel">
                    <i class="fas fa-file-import"></i> Import
                </button>
            </div>
            <input type="text" id="searchCustomer" class="form-control" placeholder="Cari pelanggan..." style="width: 200px; padding: 0.4rem;">
        </div>
    </div>
    
    <!-- Import Errors Display -->
    <?php if (session()->has('import_errors')): ?>
    <div style="padding: 1rem; background: rgba(255, 107, 53, 0.1); border-left: 4px solid var(--neon-orange); margin: 0 1rem 1rem 1rem; border-radius: 0 8px 8px 0;">
        <strong style="color: var(--neon-orange);"><i class="fas fa-exclamation-triangle"></i> Error saat Import:</strong>
        <ul style="margin: 0.5rem 0 0; padding-left: 1.5rem; color: var(--text-muted); font-size: 0.9rem;">
            <?php foreach (session('import_errors') as $err): ?>
            <li><?= esc($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <div style="overflow-x: auto;">
        <table class="data-table" id="customerTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama & Kontak</th>
                    <th>Paket & Tagihan</th>
                    <th>Status</th>
                    <th>PPPoE</th>
                    <th>Tgl Isolir</th>
                    <th>Lokasi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                        Belum ada data pelanggan
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                    <tr>
                        <td>#<?= $c['id'] ?></td>
                        <td>
                            <strong style="color: var(--text-primary);"><?= esc($c['name']) ?></strong>
                            <br>
                            <small><i class="fab fa-whatsapp"></i> <?= esc($c['phone']) ?></small>
                        </td>
                        <td>
                            <?= esc($c['package_name'] ?? 'Tanpa Paket') ?>
                            <br>
                            <small style="color: var(--neon-green);">Rp <?= number_format($c['package_price'] ?? 0, 0, ',', '.') ?></small>
                        </td>
                        <td>
                            <?php if ($c['status'] === 'active'): ?>
                                <span class="badge badge-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge badge-warning"><?= ucfirst($c['status']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code style="background: rgba(255,255,255,0.1); padding: 2px 4px; border-radius: 4px;"><?= esc($c['pppoe_username']) ?></code>
                        </td>
                        <td>
                            <span class="isolate-badge">Tgl <?= $c['isolation_date'] ?></span>
                        </td>
                        <td>
                            <?php if (!empty($c['lat'])): ?>
                                <a href="https://www.google.com/maps?q=<?= $c['lat'] ?>,<?= $c['lng'] ?>" target="_blank" style="color: var(--neon-cyan); text-decoration: none;">
                                    <i class="fas fa-map-marker-alt"></i> Lihat
                                </a>
                            <?php else: ?>
                                <span style="color: var(--text-muted);">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-secondary btn-sm" onclick="editCustomer(<?= $c['id'] ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-secondary btn-sm" onclick="createInvoice(<?= $c['id'] ?>)" title="Buat Invoice"><i class="fas fa-file-invoice"></i></button>
                            <a href="<?= base_url('admin/billing/customers/delete/' . $c['id']) ?>" class="btn btn-secondary btn-sm" title="Hapus" onclick="return confirm('Hapus pelanggan ini?')" style="background: var(--neon-red); border-color: var(--neon-red);">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Customer Modal -->
<div id="editModal" class="modal-overlay" style="display: none;">
    <div class="modal" style="max-width: 600px;">
        <div class="modal-header">
            <h3><i class="fas fa-user-edit"></i> Edit Pelanggan</h3>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form id="editCustomerForm" method="POST" action="">
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Nama Pelanggan</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nomor HP</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username PPPoE</label>
                        <input type="text" id="edit_pppoe" class="form-control" readonly style="background: rgba(255,255,255,0.05);">
                        <small style="color: var(--text-muted)">Username tidak dapat diubah</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Paket Langganan</label>
                        <select name="package_id" id="edit_package_id" class="form-control" required>
                            <?php foreach ($packages ?? [] as $pkg): ?>
                            <option value="<?= $pkg['id'] ?>"><?= esc($pkg['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Isolir</label>
                        <input type="number" name="isolation_date" id="edit_isolation_date" class="form-control" min="1" max="28" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
                    </div>
                </div>
                <div class="form-group" style="margin-top: 1rem;">
                    <label class="form-label">Alamat</label>
                    <textarea name="address" id="edit_address" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group" style="margin-top: 1rem;">
                    <label class="form-label">Koordinat (Lat, Lng)</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" name="lat" id="edit_lat" class="form-control" placeholder="Latitude">
                        <input type="text" name="lng" id="edit_lng" class="form-control" placeholder="Longitude">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Perbarui Data</button>
            </div>
        </form>
    </div>
</div>

<!-- Import Modal -->
<div id="importModal" class="modal-overlay" style="display: none;">
    <div class="modal" style="max-width: 500px;">
        <div class="modal-header">
            <h3><i class="fas fa-file-import"></i> Import Data Pelanggan</h3>
            <button class="modal-close" onclick="closeImportModal()">&times;</button>
        </div>
        <form action="<?= base_url('admin/billing/customers/import') ?>" method="POST" enctype="multipart/form-data">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-file-excel" style="color: #1D6F42;"></i> Pilih File CSV</label>
                    <input type="file" name="import_file" id="import_file" class="form-control" accept=".csv" required>
                    <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">
                        <i class="fas fa-info-circle"></i> Format yang didukung: <strong>CSV</strong> (simpan file Excel sebagai CSV UTF-8)
                    </small>
                </div>
                
                <div class="form-group" style="margin-top: 1rem;">
                    <div style="display: flex; align-items: start; gap: 10px; padding: 10px; background: rgba(0, 245, 255, 0.05); border: 1px solid var(--border-color); border-radius: 8px;">
                        <input type="checkbox" name="create_pppoe" id="import_create_pppoe" value="1" style="width: 18px; height: 18px; margin-top: 2px; cursor: pointer;">
                        <label for="import_create_pppoe" style="margin: 0; cursor: pointer; flex: 1;">
                            <strong style="color: var(--neon-cyan);"><i class="fas fa-network-wired"></i> Create PPPoE User di MikroTik</strong>
                            <br>
                            <small style="color: var(--text-muted);">Centang untuk membuat PPPoE secret di MikroTik (password = username)</small>
                        </label>
                    </div>
                </div>
                
                <div style="margin-top: 1rem; padding: 1rem; background: rgba(0, 200, 150, 0.1); border-radius: 8px; border-left: 4px solid var(--neon-green);">
                    <strong style="color: var(--neon-green);"><i class="fas fa-lightbulb"></i> Tips:</strong>
                    <ul style="margin: 0.5rem 0 0; padding-left: 1.5rem; color: var(--text-muted); font-size: 0.9rem;">
                        <li>Download <a href="<?= base_url('admin/billing/customers/template') ?>" style="color: var(--neon-cyan);">Template CSV</a> terlebih dahulu</li>
                        <li>Kolom wajib: Nama, Username PPPoE, Nama Paket</li>
                        <li>Nama paket harus sama persis dengan yang ada di sistem</li>
                        <li>Gunakan separator titik koma (;) untuk kompatibilitas Excel</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeImportModal()">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Import Sekarang</button>
            </div>
        </form>
    </div>
</div>


<style>
    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }
    .modal {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        animation: modalSlideIn 0.3s ease;
    }
    @keyframes modalSlideIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .modal-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .modal-header h3 {
        margin: 0;
        color: var(--text-primary);
    }
    .modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        color: var(--text-muted);
        cursor: pointer;
        transition: color 0.2s;
    }
    .modal-close:hover {
        color: var(--neon-red);
    }
    .modal-body {
        padding: 1.5rem;
    }
    .modal-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Map variables
    let map, marker;
    let currentLayer = 'osm';
    let osmLayer, satelliteLayer;
    let mapInitialized = false;
    
    // Initialize Map
    function initMap() {
        if (mapInitialized) {
            // Just invalidate size if already initialized
            if (map) map.invalidateSize();
            return;
        }
        
        const mapContainer = document.getElementById('map-picker');
        if (!mapContainer || mapContainer.offsetHeight === 0) {
            return; // Container not visible yet
        }
        
        mapInitialized = true;
        
        // Default center (Indonesia)
        map = L.map('map-picker').setView([-6.200000, 106.816666], 13);
        
        // OpenStreetMap Layer (default - faster)
        osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        });
        
        // Satellite Layer
        satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles © Esri'
        });
        
        // Add OSM layer by default (faster loading)
        osmLayer.addTo(map);
        
        // Click event to place marker
        map.on('click', function(e) {
            var lat = e.latlng.lat.toFixed(6);
            var lng = e.latlng.lng.toFixed(6);
            
            document.getElementById('lat').value = lat;
            document.getElementById('lng').value = lng;
            
            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng, {
                    draggable: true
                }).addTo(map);
                
                // Update coords when marker is dragged
                marker.on('dragend', function(e) {
                    var pos = marker.getLatLng();
                    document.getElementById('lat').value = pos.lat.toFixed(6);
                    document.getElementById('lng').value = pos.lng.toFixed(6);
                });
            }
        });
        
        // Try to get user location automatically
        setTimeout(getMyLocation, 500);
    }
    
    // Get user's current location
    function getMyLocation() {
        if (!map) {
            alert('Peta belum siap. Silakan buka form dulu.');
            return;
        }
        
        if (!navigator.geolocation) {
            alert('Geolocation tidak didukung browser ini');
            return;
        }
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                map.setView([lat, lng], 17);
                
                // Also set marker at current location
                if (marker) {
                    marker.setLatLng([lat, lng]);
                } else {
                    marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                    marker.on('dragend', function(e) {
                        var pos = marker.getLatLng();
                        document.getElementById('lat').value = pos.lat.toFixed(6);
                        document.getElementById('lng').value = pos.lng.toFixed(6);
                    });
                }
                document.getElementById('lat').value = lat.toFixed(6);
                document.getElementById('lng').value = lng.toFixed(6);
            },
            function(error) {
                console.log('Geolocation error:', error.message);
            },
            { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
        );
    }
    
    // Toggle between OSM and Satellite layers
    function toggleMapLayer() {
        if (!map) return;
        
        const btn = document.getElementById('layerToggle');
        
        if (currentLayer === 'osm') {
            map.removeLayer(osmLayer);
            satelliteLayer.addTo(map);
            currentLayer = 'satellite';
            btn.innerHTML = '<i class="fas fa-layer-group"></i> Street';
        } else {
            map.removeLayer(satelliteLayer);
            osmLayer.addTo(map);
            currentLayer = 'osm';
            btn.innerHTML = '<i class="fas fa-layer-group"></i> Satellite';
        }
    }
    
    // Toggle form and init map
    function toggleAddForm() {
        const formWrapper = document.getElementById('addCustomerFormWrapper');
        formWrapper.classList.toggle('hidden');
        
        if (!formWrapper.classList.contains('hidden')) {
            // Form is now visible, init map after a short delay
            setTimeout(function() {
                initMap();
                if (map) map.invalidateSize();
            }, 200);
        }
    }
    
    // Search filter
    document.getElementById('searchCustomer')?.addEventListener('input', function(e) {
        const search = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#customerTable tbody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(search) ? '' : 'none';
        });
    });
    
    // Edit customer
    function editCustomer(id) {
        fetch('<?= base_url('admin/billing/customers/get') ?>/' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const customer = data.data;
                    document.getElementById('edit_name').value = customer.name;
                    document.getElementById('edit_phone').value = customer.phone;
                    document.getElementById('edit_pppoe').value = customer.pppoe_username;
                    document.getElementById('edit_package_id').value = customer.package_id;
                    document.getElementById('edit_isolation_date').value = customer.isolation_date;
                    document.getElementById('edit_email').value = customer.email;
                    document.getElementById('edit_address').value = customer.address;
                    document.getElementById('edit_lat').value = customer.lat;
                    document.getElementById('edit_lng').value = customer.lng;
                    
                    document.getElementById('editCustomerForm').action = '<?= base_url('admin/billing/customers/edit') ?>/' + id;
                    openEditModal();
                } else {
                    alert('Gagal mengambil data pelanggan: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat mengambil data.');
            });
    }

    function openEditModal() {
        document.getElementById('editModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
        document.body.style.overflow = '';
    }
    
    // Create invoice (placeholder)
    function createInvoice(id) {
        alert('Buat invoice untuk pelanggan #' + id + '\n\nFitur invoice akan segera tersedia.');
    }
    
    // Import Modal Functions
    function openImportModal() {
        document.getElementById('importModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function closeImportModal() {
        document.getElementById('importModal').style.display = 'none';
        document.body.style.overflow = '';
        // Reset the file input
        document.getElementById('import_file').value = '';
    }
    
    // Close modal when clicking outside
    document.getElementById('importModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeImportModal();
        }
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeImportModal();
            closeEditModal();
        }
    });

    // Close modal when clicking outside
    document.getElementById('editModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });
</script>
<?= $this->endSection() ?>

