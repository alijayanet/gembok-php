<?php
/**
 * Home view – dashboard dengan kartu statistik.
 */
?>
<?= $this->extend('layout') ?>

<?= $this->section('title') ?>Dashboard - Gembok<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card">
    <h2>Statistik Sistem</h2>
    <ul>
        <li>Total Perangkat: <?= esc($totalDevices ?? 0) ?></li>
        <li>PPPoE Aktif: <?= esc($activePppoe ?? 0) ?></li>
        <li>Invoice Menunggu: <?= esc($pendingInvoices ?? 0) ?></li>
    </ul>
</div>

<div class="card">
    <h2>Perintah Cepat</h2>
    <form action="<?= base_url('admin/command') ?>" method="post">
        <input type="text" name="command" placeholder="Contoh: REBOOT 12345" style="width:70%;padding:0.5rem;" required>
        <button type="submit">Kirim</button>
    </form>
</div>
<?= $this->endSection() ?>
