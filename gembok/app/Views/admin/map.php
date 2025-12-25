<?php
/**
 * Admin Map View - ONU Location Management with Satellite Imagery
 */
?>
<?= $this->extend('layout') ?>

<?= $this->section('title') ?>Peta ONU - Gembok Admin<?= $this->endSection() ?>
<?= $this->section('page_title') ?>Peta Lokasi ONU<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Stats Row -->
<div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
    <div class="stat-card">
        <div class="stat-icon cyan">
            <i class="fas fa-satellite-dish"></i>
        </div>
        <div class="stat-info">
            <h3 id="totalMarkers">0</h3>
            <p>Total ONU</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-wifi"></i>
        </div>
        <div class="stat-info">
            <h3 id="onlineDevices">0</h3>
            <p>Online</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-info">
            <h3 id="offlineDevices">0</h3>
            <p>Offline</p>
        </div>
    </div>
</div>

<!-- Map Card (Full Width) -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-map-marked-alt"></i> Lokasi ONU</h3>
        <div style="display: flex; gap: 0.5rem;">
            <button class="btn btn-secondary btn-sm" onclick="location.reload()" title="Reload Halaman">
                <i class="fas fa-redo"></i> Reload
            </button>
            <button class="btn btn-secondary btn-sm" id="toggleLayer" title="Ganti Layer Peta">
                <i class="fas fa-layer-group"></i> Satelit
            </button>
            <button class="btn btn-secondary btn-sm" onclick="adminMap.setView([-6.200000, 106.816666], 12)">
                <i class="fas fa-crosshairs"></i> Reset
            </button>
            <button class="btn btn-primary btn-sm" onclick="loadMarkers()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>
    <div id="mapContainer" style="position: relative;">
        <!-- Loading Overlay -->
        <div id="mapLoading" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: var(--bg-secondary); display: flex; align-items: center; justify-content: center; z-index: 1000; border-radius: 12px;">
            <div style="text-align: center;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--neon-cyan); margin-bottom: 0.5rem;"></i>
                <p style="color: var(--text-secondary); margin: 0;">Memuat peta...</p>
            </div>
        </div>
        <div id="map" style="height: 500px;"></div>
    </div>
    <p style="margin-top: 0.75rem; color: var(--text-muted); font-size: 0.85rem;">
        💡 <strong>Tip:</strong> Klik marker untuk melihat detail ONU, edit WiFi SSID & Password, atau reboot ONU.
    </p>
</div>

<!-- ONU List (Small) -->
<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> ONU Terdaftar (<span id="onuCount">0</span>)</h3>
        <input type="text" id="onuSearch" class="form-control" placeholder="Cari ONU..." style="width: 200px;">
    </div>
    <div id="onuList" style="max-height: 200px; overflow-y: auto;">
        <p style="color: var(--text-muted); text-align: center;">Loading...</p>
    </div>
</div>

<!-- Edit ONU Modal -->
<div id="onuModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
    <div class="card" style="width: 400px; max-width: 90%; margin: 2rem;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-wifi"></i> Edit WiFi ONU</h3>
            <button onclick="closeModal()" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div id="onuDetails" style="margin-bottom: 1rem;">
            <p><strong>Nama:</strong> <span id="modalName">-</span></p>
            <p><strong>Serial:</strong> <code id="modalSerial" style="background: rgba(0, 245, 255, 0.1); padding: 0.25rem 0.5rem; border-radius: 4px; color: var(--neon-cyan);">-</code></p>
            <p><strong>Status:</strong> <span id="modalStatus" class="badge badge-success">Online</span></p>
        </div>
        
        <form id="editWifiForm">
            <input type="hidden" id="editSerial" value="">
            
            <div class="form-group">
                <label class="form-label">SSID (Nama WiFi)</label>
                <input type="text" id="editSsid" class="form-control" placeholder="Nama WiFi baru" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Password WiFi</label>
                <input type="password" id="editPassword" class="form-control" placeholder="Password baru (min 8 karakter)" minlength="8" required>
            </div>
            
            <div style="display: flex; gap: 0.75rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-save"></i> Simpan WiFi
                </button>
                <button type="button" class="btn btn-secondary" onclick="rebootOnu()">
                    <i class="fas fa-sync-alt"></i> Reboot
                </button>
            </div>
        </form>
        
        <div id="modalResult" style="margin-top: 1rem; display: none;"></div>
    </div>
</div>

<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
<style>
    #map {
        height: 450px;
        border-radius: 12px;
        border: 1px solid var(--border-color);
    }
    
    .leaflet-popup-content-wrapper {
        background: var(--bg-card);
        color: var(--text-primary);
        border-radius: 12px;
        box-shadow: 0 0 20px rgba(0, 245, 255, 0.3);
        border: 1px solid var(--border-color);
    }
    
    .leaflet-popup-tip {
        background: var(--bg-card);
    }
    
    .leaflet-control-zoom a {
        background: var(--bg-card) !important;
        color: var(--text-primary) !important;
        border-color: var(--border-color) !important;
    }
    
    .leaflet-control-zoom a:hover {
        background: rgba(0, 245, 255, 0.1) !important;
        color: var(--neon-cyan) !important;
    }
    
    .onu-marker {
        background: var(--neon-cyan);
        border: 3px solid white;
        border-radius: 50%;
        width: 16px;
        height: 16px;
        box-shadow: 0 0 15px var(--neon-cyan);
    }
    
    .onu-list-item {
        padding: 0.75rem;
        border-bottom: 1px solid var(--border-color);
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .onu-list-item:hover {
        background: rgba(0, 245, 255, 0.05);
    }
    
    .onu-list-item strong {
        display: block;
        margin-bottom: 0.25rem;
    }
    
    .onu-list-item small {
        color: var(--text-muted);
    }
    
    #onuModal {
        display: none;
    }
    
    #onuModal.show {
        display: flex !important;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    // Initialize map - ensure it runs fresh on every page load
    (function() {
        // Clear any existing map instance
        const mapContainer = document.getElementById('map');
        if (mapContainer._leaflet_id) {
            mapContainer._leaflet_id = null;
        }
        
        // Initialize map
        const map = L.map('map').setView([-6.200000, 106.816666], 12);
        
        // Make map globally accessible
        window.adminMap = map;
        
        // Define tile layers
        const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap',
            maxZoom: 19
        });
        
        const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri',
            maxZoom: 19
        });
        
        // Start with OSM (faster loading)
        let currentLayer = 'osm';
        osmLayer.addTo(map);
        
        // Hide loading overlay when map is ready
        map.whenReady(() => {
            document.getElementById('mapLoading').style.display = 'none';
            // Load markers after map is ready
            loadMarkers();
        });
        
        // Also hide after tiles load
        osmLayer.on('load', () => {
            document.getElementById('mapLoading').style.display = 'none';
        });
        
        // Invalidate size after a short delay to ensure proper rendering
        setTimeout(() => {
            map.invalidateSize();
        }, 100);
        
        // Layer toggle button
        document.getElementById('toggleLayer').addEventListener('click', function() {
            const btn = this;
            if (currentLayer === 'osm') {
                map.removeLayer(osmLayer);
                satelliteLayer.addTo(map);
                btn.innerHTML = '<i class="fas fa-layer-group"></i> Street';
                currentLayer = 'satellite';
            } else {
                map.removeLayer(satelliteLayer);
                osmLayer.addTo(map);
                btn.innerHTML = '<i class="fas fa-layer-group"></i> Satelit';
                currentLayer = 'osm';
            }
        });
        
        // Custom marker icon
        const onuIcon = L.divIcon({
            className: 'onu-marker',
            iconSize: [16, 16]
        });
        
        let markers = [];
        let onuData = [];
        
        // Load ONU markers from API
        window.loadMarkers = function() {
            markers.forEach(m => map.removeLayer(m));
            markers = [];
            
            fetch('<?= base_url('api/onuLocations') ?>')
                .then(r => r.json())
                .then(data => {
                    onuData = data;
                    document.getElementById('totalMarkers').textContent = data.length;
                    document.getElementById('onlineDevices').textContent = data.length;
                    document.getElementById('offlineDevices').textContent = 0;
                    
                    // Update list
                    updateOnuList(data);
                    
                    data.forEach(loc => {
                        if (loc.lat && loc.lng) {
                            const marker = L.marker([loc.lat, loc.lng], { icon: onuIcon }).addTo(map);
                            marker.bindPopup(createPopupContent(loc));
                            marker.on('popupopen', () => loadPopupDetails(loc.serial, loc.name));
                            markers.push(marker);
                        }
                    });
                })
                .catch(err => console.error('Error loading ONU:', err));
        };
        
        // Create popup skeleton
        function createPopupContent(loc) {
            const s = loc.serial || 'unknown';
            return `
                <div id="popup-${s}" style="min-width: 260px; font-family: 'Segoe UI', sans-serif;">
                    <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; margin-bottom: 0.5rem;">
                        <strong>${s}</strong><br>
                        <small>${loc.name || 'N/A'}</small>
                    </div>
                    <div class="popup-body">
                        <p style="color: var(--neon-cyan); text-align: center;">⏳ Memuat data GenieACS...</p>
                    </div>
                </div>
            `;
        }

        async function loadPopupDetails(serial, savedName) {
            const container = document.querySelector(`#popup-${serial} .popup-body`);
            if (!container) return;

            try {
                const res = await fetch(`<?= base_url('api/onu/detail') ?>?serial=${serial}`);
                const data = await res.json();
                
                if (!data || !data._deviceId) {
                    container.innerHTML = '<p style="color: var(--neon-pink);">Data tidak ditemukan di GenieACS</p>';
                    return;
                }

                // Helpers
                const getVal = (path) => {
                    let v = path;
                    if (typeof v === 'object' && v !== null && v._value !== undefined) return v._value;
                    return v;
                };
                const val = (obj, key) => {
                    if (!obj) return '-';
                    const keys = key.split('.');
                    let curr = obj;
                    for(let k of keys) {
                        curr = curr[k];
                        if(curr === undefined) return '-';
                    }
                    return getVal(curr);
                }

                const vp = data.VirtualParameters || {};
                const pppoeUser = getVal(vp.pppoeUsername) || getVal(vp.pppoeUsername2) || '-';
                const rx = getVal(vp.RXPower) || '-';
                const ssid = val(data, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID');
                const assoc = getVal(vp.activedevices) || getVal(vp.useraktif) || val(data, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.TotalAssociations') || '0';
                const lastInform = data._lastInform;
                const isOnline = lastInform && (Date.now() - new Date(lastInform).getTime() < 300000);
                const statusHtml = isOnline 
                    ? '<span class="badge badge-success" style="font-size:0.8rem">Online</span>' 
                    : '<span class="badge badge-warning" style="font-size:0.8rem">Offline</span>';
                const tags = (data.tags || []).join(', ') || 'Tidak ada tag';
                
                let html = `
                    <div style="margin-bottom: 0.5rem;">
                        ${statusHtml}
                    </div>
                    
                    <div style="font-size: 0.9rem; line-height: 1.4;">
                        <div style="margin-bottom: 4px;"><span style="color: var(--text-muted);">PPPoE User:</span><br><strong style="color: var(--neon-purple);">${pppoeUser}</strong></div>
                        <div style="margin-bottom: 4px;"><span style="color: var(--text-muted);">Pelanggan:</span><br>${savedName}</div>
                        
                        <hr style="border-color: rgba(255,255,255,0.1); margin: 6px 0;">

                        <div style="margin-bottom: 4px;"><span style="color: var(--text-muted);">WiFi SSID:</span><br><strong style="color: var(--neon-cyan);">${ssid}</strong></div>
                        <div style="margin-bottom: 4px;"><span style="color: var(--text-muted);">Total User:</span> <strong>${assoc} perangkat</strong></div>
                        <div style="margin-bottom: 4px;"><span style="color: var(--text-muted);">Rx Power:</span> 
                            <strong style="${parseFloat(rx) < -25 ? 'color: var(--neon-pink);' : 'color: var(--neon-green);'}">${rx} dBm</strong>
                        </div>
                         <div style="margin-bottom: 4px;"><small style="color: var(--text-muted);">Tags: ${tags}</small></div>
                    </div>

                    <hr style="border-color: rgba(255,255,255,0.1); margin: 8px 0;">
                    
                    <form onsubmit="quickEditWifi(event, '${serial}')">
                        <label style="font-size: 0.8rem; color: var(--text-muted);">Edit SSID:</label>
                        <input type="text" name="ssid" value="${ssid}" class="form-control" style="padding: 4px; font-size: 0.9rem; margin-bottom: 4px;">
                        
                        <label style="font-size: 0.8rem; color: var(--text-muted);">Edit Password:</label>
                        <input type="text" name="password" placeholder="New Password" class="form-control" style="padding: 4px; font-size: 0.9rem; margin-bottom: 6px;">
                        
                        <button type="submit" class="btn btn-primary btn-sm" style="width: 100%;">Simpan</button>
                        <div class="result-msg" style="margin-top:4px;"></div>
                    </form>
                `;
                
                container.innerHTML = html;

            } catch (e) {
                container.innerHTML = `<p style="color: var(--neon-pink);">Error: ${e.message}</p>`;
            }
        }

        window.quickEditWifi = async function(e, serial) {
            e.preventDefault();
            const output = e.target.querySelector('.result-msg');
            const ssid = e.target.ssid.value;
            const password = e.target.password.value;
            
            if(!password) {
                output.innerHTML = '<span style="color: var(--neon-orange);">Password wajib diisi!</span>';
                return;
            }

            output.innerHTML = '<span style="color: var(--neon-cyan);">Menyimpan...</span>';
            
            try {
                 const res = await fetch('<?= base_url('api/onu/wifi') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ serial, ssid, password })
                });
                const result = await res.json();
                 if (result.success) {
                    output.innerHTML = '<span style="color: var(--neon-green);">✅ Berhasil!</span>';
                } else {
                    output.innerHTML = `<span style="color: var(--neon-pink);">❌ ${result.message}</span>`;
                }
            } catch(err) {
                 output.innerHTML = `<span style="color: var(--neon-pink);">Error</span>`;
            }
        };
        
        // Update ONU list panel
        function updateOnuList(data) {
            const listEl = document.getElementById('onuList');
            const countEl = document.getElementById('onuCount');
            
            if (countEl) countEl.textContent = data.length;
            
            if (data.length === 0) {
                listEl.innerHTML = '<p style="color: var(--text-muted); text-align: center;">Belum ada ONU terdaftar. Mapping ONU dari halaman GenieACS.</p>';
                return;
            }
            
            listEl.innerHTML = data.map(loc => `
                <div class="onu-list-item" onclick="focusOnu(${loc.lat}, ${loc.lng})">
                    <strong>${loc.name || 'Unnamed'}</strong>
                    <small>${loc.serial || 'No Serial'}</small>
                </div>
            `).join('');
        }
        
        // Focus map on ONU
        window.focusOnu = function(lat, lng) {
            map.setView([lat, lng], 17);
        };
        
        // Open edit modal
        window.openModal = function(loc) {
            document.getElementById('modalName').textContent = loc.name || 'Unnamed ONU';
            document.getElementById('modalSerial').textContent = loc.serial || '-';
            document.getElementById('editSerial').value = loc.serial || '';
            document.getElementById('onuModal').classList.add('show');
        };
        
        window.closeModal = function() {
            document.getElementById('onuModal').classList.remove('show');
            document.getElementById('modalResult').style.display = 'none';
        };
        
        // Edit WiFi form submit
        document.getElementById('editWifiForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const resultEl = document.getElementById('modalResult');
            resultEl.style.display = 'block';
            resultEl.innerHTML = '<p style="color: var(--neon-cyan);">⏳ Memproses...</p>';
            
            const data = {
                serial: document.getElementById('editSerial').value,
                ssid: document.getElementById('editSsid').value,
                password: document.getElementById('editPassword').value
            };
            
            try {
                const res = await fetch('<?= base_url('api/onu/wifi') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (result.success) {
                    resultEl.innerHTML = '<p style="color: var(--neon-green);">✅ WiFi berhasil diperbarui!</p>';
                } else {
                    resultEl.innerHTML = `<p style="color: var(--neon-orange);">❌ ${result.message || 'Gagal'}</p>`;
                }
            } catch (err) {
                resultEl.innerHTML = `<p style="color: var(--neon-pink);">❌ Error: ${err.message}</p>`;
            }
        });
        
        // Reboot ONU
        window.rebootOnu = async function() {
            const serial = document.getElementById('editSerial').value;
            if (!serial) return alert('Serial tidak ditemukan');
            
            if (!confirm('Yakin ingin reboot ONU ini?')) return;
            
            const resultEl = document.getElementById('modalResult');
            resultEl.style.display = 'block';
            resultEl.innerHTML = '<p style="color: var(--neon-cyan);">⏳ Mengirim perintah reboot...</p>';
            
            try {
                const res = await fetch('<?= base_url('admin/command') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'command=REBOOT ' + encodeURIComponent(serial)
                });
                const result = await res.json();
                resultEl.innerHTML = `<p style="color: var(--neon-green);">${result.msg}</p>`;
            } catch (err) {
                resultEl.innerHTML = `<p style="color: var(--neon-pink);">❌ Error: ${err.message}</p>`;
            }
        };
        
        // Search ONU in list
        const onuSearchInput = document.getElementById('onuSearch');
        if (onuSearchInput) {
            onuSearchInput.addEventListener('input', function(e) {
                const search = e.target.value.toLowerCase();
                const items = document.querySelectorAll('.onu-list-item');
                items.forEach(item => {
                    const text = item.textContent.toLowerCase();
                    item.style.display = text.includes(search) ? '' : 'none';
                });
            });
        }
    })();
</script>
<?= $this->endSection() ?>
