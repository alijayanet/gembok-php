<?php
/**
 * Partial view: navigation bar with links and theme toggle.
 * Used in layout.php via $this->include('partials/navbar').
 */
?>
<nav class="navbar">
    <ul class="nav-list">
        <li><a href="<?= base_url('/') ?>">Dashboard</a></li>
        <li><a href="<?= base_url('admin/map') ?>">Peta ONU</a></li>
        <li><a href="<?= base_url('admin/command') ?>">Perintah</a></li>
    </ul>
    <div class="toggle-theme" id="theme-toggle" title="Toggle dark / light theme">
        🌙/☀️
    </div>
</nav>
