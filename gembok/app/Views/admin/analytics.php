<?php
/**
 * Analytics View - Financial & Performance Reports
 */
?>
<?= $this->extend('layout') ?>

<?= $this->section('title') ?>Analytics - Gembok Admin<?= $this->endSection() ?>
<?= $this->section('page_title') ?>Analytics<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Stats Row -->
<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="stat-info">
            <h3>Rp 0</h3>
            <p>Pendapatan Bulan Ini</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon cyan">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
            <h3>0</h3>
            <p>Invoice Lunas</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-info">
            <h3>0</h3>
            <p>Invoice Belum Lunas</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <h3>0</h3>
            <p>Total Pelanggan</p>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
    <!-- Revenue Chart -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-area"></i> Grafik Pendapatan</h3>
            <select class="form-control" style="width: auto; padding: 0.5rem 1rem;">
                <option>6 Bulan Terakhir</option>
                <option>12 Bulan Terakhir</option>
                <option>Tahun Ini</option>
            </select>
        </div>
        <div id="revenueChart" style="height: 300px; display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
            <div style="text-align: center;">
                <i class="fas fa-chart-line" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <p>Grafik akan tampil setelah ada data invoice</p>
            </div>
        </div>
    </div>
    
    <!-- Monthly Summary -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Rekap Bulanan</h3>
        </div>
        <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
            <i class="fas fa-file-invoice-dollar" style="font-size: 3rem; margin-bottom: 1rem;"></i>
            <p>Belum ada data invoice</p>
        </div>
    </div>
</div>

<!-- Payment Status -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list-alt"></i> Status Pembayaran Terbaru</h3>
        <button class="btn btn-primary btn-sm">
            <i class="fas fa-download"></i> Export
        </button>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Pelanggan</th>
                <th>Invoice</th>
                <th>Jumlah</th>
                <th>Status</th>
                <th>Tanggal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                    Belum ada data pembayaran
                </td>
            </tr>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>
