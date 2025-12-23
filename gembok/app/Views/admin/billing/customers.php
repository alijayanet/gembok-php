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
        <div style="margin-left: auto;">
             <input type="text" id="searchCustomer" class="form-control" placeholder="Cari pelanggan..." style="width: 200px; padding: 0.4rem;">
        </div>
    </div>
    
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
    
    // Edit customer (placeholder)
    function editCustomer(id) {
        alert('Edit pelanggan #' + id + '\n\nFitur edit akan segera tersedia.');
    }
    
    // Create invoice (placeholder)
    function createInvoice(id) {
        alert('Buat invoice untuk pelanggan #' + id + '\n\nFitur invoice akan segera tersedia.');
    }
</script>
<?= $this->endSection() ?>

