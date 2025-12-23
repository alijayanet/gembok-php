<?php
/**
 * GenieACS Device Management View
 */
?>
<?= $this->extend('layout') ?>

<?= $this->section('title') ?>GenieACS - Gembok Admin<?= $this->endSection() ?>
<?= $this->section('page_title') ?>GenieACS Device Management<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
    $totalDevices = count($devices ?? []);
    $onlineCount = 0;
    $offlineCount = 0;
    $weakSignalCount = 0;

    foreach ($devices as $d) {
        // Online check
        $lastInform = $d['_lastInform'] ?? null;
        if ($lastInform && (time() - strtotime($lastInform) < 300)) {
            $onlineCount++;
        } else {
            $offlineCount++;
        }

        // Weak signal check
        $vp = $d['VirtualParameters'] ?? [];
        // Helper to extract value
        $val = $vp['RXPower'] ?? '-';
        if (is_array($val) && isset($val['_value'])) $val = $val['_value'];
        elseif (is_array($val)) $val = '-'; // invalid
        
        $rx = floatval($val);
        // Assuming typical PON limits: -25 or lower is weak/bad
        if ($rx != 0 && $rx < -25) {
            $weakSignalCount++;
        }
    }
?>
<!-- Stats Row -->
<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card">
        <div class="stat-icon cyan">
            <i class="fas fa-satellite-dish"></i>
        </div>
        <div class="stat-info">
            <h3 id="totalDevices"><?= $totalDevices ?></h3>
            <p>Total Device</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
            <h3 id="onlineDevices"><?= $onlineCount ?></h3>
            <p>Online</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-times-circle"></i>
        </div>
        <div class="stat-info">
            <h3 id="offlineDevices"><?= $offlineCount ?></h3>
            <p>Offline</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-info">
            <h3><?= $weakSignalCount ?></h3>
            <p>Signal Lemah</p>
        </div>
    </div>
</div>

<!-- Device Table -->
<div class="card" style="overflow-x: auto;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-server"></i> Daftar Device ONU</h3>
        <div style="display: flex; gap: 0.5rem;">
            <input type="text" id="searchDevice" class="form-control" placeholder="Cari device..." style="width: 250px;">
            <button class="btn btn-primary btn-sm" onclick="refreshDevices()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>
    
    <table class="data-table" id="deviceTable">
        <thead>
            <tr>
                <th>PPPoE User / Model</th>
                <th>IP / MAC</th>
                <th>SSID (Clients)</th>
                <th>Status Optik</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($devices)): ?>
            <tr>
                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                    <i class="fas fa-server" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                    Belum ada device terdaftar di GenieACS (atau gagal koneksi API)
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($devices as $device): 
                    // Helper to get nested value safely
                    $vp = $device['VirtualParameters'] ?? [];
                    $wlan = $device['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][1] ?? [];
                    
                    $serial = $device['_deviceId']['_SerialNumber'] ?? '-';
                    // Helper to recursively finding '_value' if available
                    $getValue = function($val) {
                        if (is_array($val) && isset($val['_value'])) {
                            return (string)$val['_value'];
                        }
                        return is_array($val) ? json_encode($val) : (string)$val;
                    };

                    $pppoeUserRaw = $vp['pppoeUsername'] ?? $vp['pppoeUsername2'] ?? '-';
                    $pppoeUser = $getValue($pppoeUserRaw);

                    $ipRaw = $vp['pppoeIP'] ?? $vp['IPTR069'] ?? '-';
                    $ip = $getValue($ipRaw);

                    $macRaw = $vp['pppoeMac'] ?? $vp['PonMac'] ?? '-';
                    $mac = $getValue($macRaw);

                    $rxPowerRaw = $vp['RXPower'] ?? '-';
                    $rxPower = $getValue($rxPowerRaw);
                    
                    $ssidRaw = $wlan['SSID'] ?? '-';
                    $ssid = $getValue($ssidRaw);
                    
                    $assocRaw = $wlan['TotalAssociations'] ?? $vp['activedevices'] ?? $vp['useraktif'] ?? 0;
                    $assoc = $getValue($assocRaw);
                    
                    $tempRaw = $vp['gettemp'] ?? '';
                    $temp = $getValue($tempRaw);
                    
                    $lastInform = $device['_lastInform'] ?? null;
                    
                    // Simple online check (within last 5 minutes)
                    $isOnline = false;
                    if ($lastInform) {
                        $diff = time() - strtotime($lastInform);
                        $isOnline = $diff < 300; 
                    }
                ?>
                <tr>
                    <td>
                        <strong style="color: var(--neon-purple);"><?= esc($pppoeUser) ?></strong>
                        <br><small class="text-muted"><?= esc($device['_deviceId']['_ProductClass'] ?? '') ?></small>
                    </td>
                    <td>
                        <div style="font-size: 0.85rem;">IP: <?= esc($ip) ?></div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">MAC: <?= esc($mac) ?></div>
                    </td>
                    <td>
                        <span style="color: var(--neon-cyan);"><?= esc($ssid) ?></span>
                        <br>
                        <small><i class="fas fa-users"></i> <?= esc($assoc) ?> user</small>
                    </td>
                    <td>
                        <div>RX: <span style="<?= (floatval($rxPower) < -25) ? 'color: var(--neon-pink);' : 'color: var(--neon-green);' ?>"><?= esc($rxPower) ?> dBm</span></div>
                        <?php if($temp): ?>
                        <small>Temp: <?= esc($temp) ?>°C</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($isOnline): ?>
                            <span class="badge badge-success">Online</span>
                            <br><small><?= date('H:i', strtotime($lastInform)) ?></small>
                        <?php else: ?>
                            <span class="badge badge-warning">Offline</span>
                            <br><small><?= $lastInform ? date('d/m H:i', strtotime($lastInform)) : '-' ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                            <button class="btn btn-secondary btn-sm" data-action="edit-ssid" data-serial="<?= esc($serial) ?>" title="Edit SSID & Password WiFi">
                                <i class="fas fa-wifi"></i>
                            </button>
                            <button class="btn btn-primary btn-sm" data-action="open-map" data-serial="<?= esc($serial) ?>" data-pppoe="<?= esc($pppoeUser) ?>" title="Set Lokasi / Mapping Koordinat">
                                <i class="fas fa-map-marker-alt"></i> Map
                            </button>
                            <button class="btn btn-secondary btn-sm" data-action="reboot" data-serial="<?= esc($serial) ?>" title="Restart Device">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Edit SSID Modal -->
<div id="ssidModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
    <div class="card" style="width: 400px; max-width: 90%; margin: 2rem;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-wifi"></i> Edit SSID & Password</h3>
            <button onclick="closeModal()" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="editSsidForm">
            <input type="hidden" id="deviceSerial" value="">
            
            <div class="form-group">
                <label class="form-label">SSID (Nama WiFi)</label>
                <input type="text" id="newSsid" class="form-control" placeholder="Nama WiFi baru" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Password WiFi</label>
                <input type="password" id="newPassword" class="form-control" placeholder="Password baru (min 8 karakter)" minlength="8" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <i class="fas fa-save"></i> Simpan Perubahan
            </button>
        </form>
        
        <div id="modalResult" style="margin-top: 1rem; display: none;"></div>
    </div>
</div>

<!-- Mapping Koordinat Modal -->
<div id="mapModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); z-index: 2000; align-items: center; justify-content: center;">
    <div class="card" style="width: 700px; max-width: 95%; margin: 1rem; max-height: 95vh; overflow-y: auto;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-map-marker-alt"></i> Set Lokasi ONU</h3>
            <button onclick="closeMapModal()" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div style="padding: 0 1rem;">
            <p style="color: var(--text-muted); margin-bottom: 0.75rem; font-size: 0.9rem;">
                <i class="fas fa-info-circle"></i> Klik pada peta untuk menentukan lokasi ONU, atau gunakan tombol "Lokasi Saya"
            </p>
            
            <div style="display: flex; gap: 0.5rem; margin-bottom: 0.75rem; flex-wrap: wrap; align-items: center;">
                <strong style="color: var(--neon-purple);">Serial:</strong>
                <code id="mapSerialDisplay" style="background: rgba(0, 245, 255, 0.1); padding: 0.25rem 0.5rem; border-radius: 4px; color: var(--neon-cyan);">-</code>
                <strong style="color: var(--neon-purple); margin-left: 1rem;">PPPoE:</strong>
                <span id="mapPppoeDisplay" style="color: var(--text-secondary);">-</span>
            </div>
        </div>
        
        <!-- Mini Map -->
        <div id="miniMap" style="height: 300px; margin: 0 1rem; border-radius: 8px; border: 1px solid var(--border-color);"></div>
        
        <form id="mappingForm" style="padding: 1rem;">
            <input type="hidden" id="mapSerial" value="">
            
            <div class="form-group">
                <label class="form-label">Nama Pelanggan / Lokasi</label>
                <input type="text" id="mapName" class="form-control" placeholder="Nama pelanggan atau alamat" required>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="form-group">
                    <label class="form-label">Latitude</label>
                    <input type="number" id="mapLat" class="form-control" step="0.000001" placeholder="-6.200000" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Longitude</label>
                    <input type="number" id="mapLng" class="form-control" step="0.000001" placeholder="106.816666" required>
                </div>
            </div>
            
            <div style="display: flex; gap: 0.5rem; margin-bottom: 0.75rem;">
                <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="getMyLocation()">
                    <i class="fas fa-location-arrow"></i> Lokasi Saya
                </button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-save"></i> Simpan Lokasi
                </button>
            </div>
        </form>
        
        <div id="mapModalResult" style="padding: 0 1rem 1rem; display: none;"></div>
    </div>
</div>

<!-- Leaflet CSS for Modal Map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
<style>
    #miniMap .leaflet-popup-content-wrapper {
        background: var(--bg-card);
        color: var(--text-primary);
        border-radius: 8px;
    }
    #miniMap .leaflet-popup-tip {
        background: var(--bg-card);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
// ===========================================
// MINI MAP FOR MODAL
// ===========================================
let miniMap = null;
let miniMapMarker = null;

function initMiniMap() {
    if (miniMap) return; // Already initialized
    
    miniMap = L.map('miniMap').setView([-6.200000, 106.816666], 12);
    
    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri',
        maxZoom: 19
    }).addTo(miniMap);
    
    // Click handler
    miniMap.on('click', function(e) {
        setMapCoordinates(e.latlng.lat, e.latlng.lng);
    });
}

function setMapCoordinates(lat, lng) {
    document.getElementById('mapLat').value = lat.toFixed(6);
    document.getElementById('mapLng').value = lng.toFixed(6);
    
    // Update or create marker
    if (miniMapMarker) {
        miniMapMarker.setLatLng([lat, lng]);
    } else {
        miniMapMarker = L.marker([lat, lng]).addTo(miniMap);
    }
    
    miniMap.setView([lat, lng], 17);
}

// ===========================================
// EVENT DELEGATION - Works with dynamic content
// ===========================================
document.addEventListener('click', function(e) {
    const target = e.target.closest('[data-action]');
    if (!target) return;
    
    const action = target.dataset.action;
    const serial = target.dataset.serial;
    const pppoe = target.dataset.pppoe;
    
    if (action === 'edit-ssid' && serial) {
        editSsid(serial);
    } else if (action === 'open-map' && serial) {
        openMapModal(serial, pppoe);
    } else if (action === 'reboot' && serial) {
        rebootDevice(serial);
    }
});

function editSsid(serial) {
    const modal = document.getElementById('ssidModal');
    const serialInput = document.getElementById('deviceSerial');
    if (modal && serialInput) {
        serialInput.value = serial;
        modal.style.display = 'flex';
    }
}

function closeModal() {
    const modal = document.getElementById('ssidModal');
    const result = document.getElementById('modalResult');
    if (modal) modal.style.display = 'none';
    if (result) result.style.display = 'none';
}

function openMapModal(serial, pppoe) {
    // Set values
    document.getElementById('mapSerial').value = serial;
    document.getElementById('mapSerialDisplay').textContent = serial;
    document.getElementById('mapPppoeDisplay').textContent = pppoe || '-';
    
    // Reset form
    document.getElementById('mapName').value = '';
    document.getElementById('mapLat').value = '';
    document.getElementById('mapLng').value = '';
    document.getElementById('mapModalResult').style.display = 'none';
    
    // Show modal
    document.getElementById('mapModal').style.display = 'flex';
    
    // Initialize map after modal is visible
    setTimeout(() => {
        initMiniMap();
        miniMap.invalidateSize();
        
        // Remove old marker
        if (miniMapMarker) {
            miniMap.removeLayer(miniMapMarker);
            miniMapMarker = null;
        }
    }, 100);
}

function closeMapModal() {
    document.getElementById('mapModal').style.display = 'none';
    document.getElementById('mapModalResult').style.display = 'none';
}

function getMyLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            pos => {
                setMapCoordinates(pos.coords.latitude, pos.coords.longitude);
            },
            err => {
                alert('Tidak bisa mendapatkan lokasi: ' + err.message);
            }
        );
    } else {
        alert('Browser tidak support geolocation');
    }
}

async function rebootDevice(serial) {
    if (!confirm('Yakin ingin restart device ' + serial + '?')) return;
    
    try {
        const res = await fetch('<?= base_url('admin/command') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'command=REBOOT ' + encodeURIComponent(serial)
        });
        const result = await res.json();
        alert(result.msg);
    } catch (err) {
        alert('Error: ' + err.message);
    }
}

function refreshDevices() {
    location.reload();
}

// Form submit - SSID Edit
const editSsidForm = document.getElementById('editSsidForm');
if (editSsidForm) {
    editSsidForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const resultEl = document.getElementById('modalResult');
        resultEl.style.display = 'block';
        resultEl.innerHTML = '<p style="color: var(--neon-cyan);">⏳ Memproses...</p>';
        
        const data = {
            serial: document.getElementById('deviceSerial').value,
            ssid: document.getElementById('newSsid').value,
            password: document.getElementById('newPassword').value
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
}

// Form submit - Mapping
const mappingForm = document.getElementById('mappingForm');
if (mappingForm) {
    mappingForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const resultEl = document.getElementById('mapModalResult');
        resultEl.style.display = 'block';
        resultEl.innerHTML = '<p style="color: var(--neon-cyan);">⏳ Menyimpan lokasi...</p>';
        
        const data = {
            name: document.getElementById('mapName').value,
            serial: document.getElementById('mapSerial').value,
            lat: document.getElementById('mapLat').value,
            lng: document.getElementById('mapLng').value
        };
        
        if (!data.lat || !data.lng) {
            resultEl.innerHTML = '<p style="color: var(--neon-orange);">❌ Silakan klik peta untuk menentukan koordinat!</p>';
            return;
        }
        
        try {
            const res = await fetch('<?= base_url('api/onu/add') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) {
                resultEl.innerHTML = '<p style="color: var(--neon-green);">✅ Lokasi ONU berhasil disimpan!</p>';
                // Close modal after 1.5 seconds
                setTimeout(() => {
                    closeMapModal();
                }, 1500);
            } else {
                resultEl.innerHTML = `<p style="color: var(--neon-orange);">❌ ${result.message || 'Gagal menyimpan'}</p>`;
            }
        } catch (err) {
            resultEl.innerHTML = `<p style="color: var(--neon-pink);">❌ Error: ${err.message}</p>`;
        }
    });
}

// Search filter with event delegation
const searchInput = document.getElementById('searchDevice');
if (searchInput) {
    searchInput.addEventListener('input', function(e) {
        const search = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#deviceTable tbody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(search) ? '' : 'none';
        });
    });
}
</script>
<?= $this->endSection() ?>
