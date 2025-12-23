<?php
/**
 * ODP Management View
 */
?>
<?= $this->extend('layout') ?>

<?= $this->section('title') ?>Manajemen ODP - Gembok Admin<?= $this->endSection() ?>
<?= $this->section('page_title') ?>Manajemen ODP<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Stats Row -->
<div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
    <div class="stat-card">
        <div class="stat-icon cyan">
            <i class="fas fa-project-diagram"></i>
        </div>
        <div class="stat-info">
            <h3>0</h3>
            <p>Total ODP</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-plug"></i>
        </div>
        <div class="stat-info">
            <h3>0</h3>
            <p>Port Tersedia</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-satellite-dish"></i>
        </div>
        <div class="stat-info">
            <h3>0</h3>
            <p>ONU Terhubung</p>
        </div>
    </div>
</div>

<!-- ODP Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-project-diagram"></i> Daftar ODP</h3>
        <button class="btn btn-primary btn-sm" onclick="addOdp()">
            <i class="fas fa-plus"></i> Tambah ODP
        </button>
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Nama ODP</th>
                <th>Lokasi</th>
                <th>Kapasitas</th>
                <th>Terpakai</th>
                <th>Tersedia</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                    <i class="fas fa-project-diagram" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                    Belum ada ODP terdaftar
                </td>
            </tr>
        </tbody>
    </table>
</div>

<!-- ODP Hierarchy -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-sitemap"></i> Hierarki Jaringan</h3>
    </div>
    <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
        <i class="fas fa-network-wired" style="font-size: 3rem; margin-bottom: 1rem;"></i>
        <p>Visualisasi hierarki ODP akan tampil setelah ada data</p>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function addOdp() {
    alert('Fitur tambah ODP akan segera tersedia');
}
</script>
<?= $this->endSection() ?>
