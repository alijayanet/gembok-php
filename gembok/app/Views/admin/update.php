<?php
/**
 * System Update Page
 * Halaman untuk update aplikasi Gembok dengan sidebar
 */
?>
<?= $this->extend('layout') ?>

<?= $this->section('title') ?>Update Sistem - Gembok Admin<?= $this->endSection() ?>
<?= $this->section('page_title') ?>Update Sistem<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Stats Grid -->
<div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
    <div class="stat-card">
        <div class="stat-icon cyan">
            <i class="fas fa-code-branch"></i>
        </div>
        <div class="stat-info">
            <h3 style="font-size: 1.2rem;"><?= esc($currentVersion) ?></h3>
            <p>Versi Saat Ini</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fab fa-github"></i>
        </div>
        <div class="stat-info">
            <h3 style="font-size: 1rem;"><?= esc($githubRepo) ?></h3>
            <p>Repository</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="fas fa-archive"></i>
        </div>
        <div class="stat-info">
            <?php if ($lastBackup): ?>
            <h3 style="font-size: 0.9rem;"><?= esc($lastBackup['size']) ?></h3>
            <p>Backup Terakhir</p>
            <?php else: ?>
            <h3 style="font-size: 1rem;">-</h3>
            <p>Belum Ada Backup</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Update Card -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-cloud-download-alt"></i> Update Aplikasi</h3>
    </div>
    
    <div style="padding: 1.5rem 0;">
        <?php if ($updateFileExists): ?>
        <!-- Update Info -->
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
            <div style="background: rgba(0, 245, 255, 0.05); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem;">
                <h4 style="margin-bottom: 1rem; color: var(--neon-cyan);"><i class="fas fa-info-circle"></i> Informasi Update</h4>
                <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.9rem;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Source:</span>
                        <span><a href="https://github.com/<?= esc($githubRepo) ?>" target="_blank" style="color: var(--neon-cyan);">GitHub</a></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Branch:</span>
                        <span><code><?= esc($githubBranch) ?></code></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Metode:</span>
                        <span>Download ZIP + Overwrite</span>
                    </div>
                </div>
            </div>
            
            <div style="background: rgba(0, 255, 136, 0.05); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem;">
                <h4 style="margin-bottom: 1rem; color: var(--neon-green);"><i class="fas fa-shield-alt"></i> Keamanan</h4>
                <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.9rem;">
                    <li style="margin-bottom: 0.5rem;"><i class="fas fa-check" style="color: var(--neon-green); margin-right: 0.5rem;"></i> Backup otomatis sebelum update</li>
                    <li style="margin-bottom: 0.5rem;"><i class="fas fa-check" style="color: var(--neon-green); margin-right: 0.5rem;"></i> File .env tidak akan ditimpa</li>
                    <li style="margin-bottom: 0.5rem;"><i class="fas fa-check" style="color: var(--neon-green); margin-right: 0.5rem;"></i> Folder vendor/ tidak ditimpa</li>
                    <li><i class="fas fa-check" style="color: var(--neon-green); margin-right: 0.5rem;"></i> Rollback tersedia jika gagal</li>
                </ul>
            </div>
        </div>
        
        <!-- Update Button -->
        <div style="text-align: center; padding: 2rem; background: rgba(255, 255, 255, 0.02); border-radius: 12px; border: 1px dashed var(--border-color);">
            <i class="fas fa-rocket" style="font-size: 3rem; color: var(--neon-cyan); margin-bottom: 1rem;"></i>
            <h3 style="margin-bottom: 0.5rem;">Siap untuk Update?</h3>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">
                Pastikan tidak ada aktivitas penting yang sedang berjalan sebelum melakukan update.
            </p>
            <a href="<?= base_url('update.php') ?>" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1rem;">
                <i class="fas fa-download"></i> Mulai Update Sekarang
            </a>
            <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 1rem;">
                <i class="fas fa-info-circle"></i> Anda akan diarahkan ke halaman proses update
            </p>
        </div>
        
        <?php else: ?>
        <!-- Update File Not Found -->
        <div style="text-align: center; padding: 3rem; background: rgba(255, 107, 53, 0.05); border-radius: 12px; border: 1px solid rgba(255, 107, 53, 0.3);">
            <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--neon-orange); margin-bottom: 1rem;"></i>
            <h3 style="margin-bottom: 0.5rem; color: var(--neon-orange);">File Update Tidak Ditemukan</h3>
            <p style="color: var(--text-muted); margin-bottom: 1rem;">
                File <code>update.php</code> tidak ditemukan di root folder. Pastikan file tersebut ada untuk menjalankan auto-update.
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Backup History Card -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history"></i> Riwayat Backup</h3>
    </div>
    
    <?php if ($lastBackup): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>File</th>
                <th>Tanggal</th>
                <th>Ukuran</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code><?= esc($lastBackup['file']) ?></code></td>
                <td><?= esc($lastBackup['date']) ?></td>
                <td><?= esc($lastBackup['size']) ?></td>
                <td>
                    <a href="<?= base_url('backups/' . $lastBackup['file']) ?>" class="btn btn-secondary btn-sm" download>
                        <i class="fas fa-download"></i> Download
                    </a>
                </td>
            </tr>
        </tbody>
    </table>
    <?php else: ?>
    <div style="padding: 2rem; text-align: center; color: var(--text-muted);">
        <i class="fas fa-archive" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
        <p>Belum ada backup yang tersedia.</p>
        <p style="font-size: 0.9rem;">Backup akan otomatis dibuat saat proses update dijalankan.</p>
    </div>
    <?php endif; ?>
</div>

<!-- Tips Card -->
<div class="card" style="border-left: 4px solid var(--neon-cyan);">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-lightbulb"></i> Tips Update</h3>
    </div>
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
        <div>
            <h4 style="font-size: 0.95rem; margin-bottom: 0.5rem; color: var(--text-primary);"><i class="fas fa-check-circle" style="color: var(--neon-green);"></i> Sebelum Update</h4>
            <ul style="list-style: disc; padding-left: 1.5rem; color: var(--text-muted); font-size: 0.9rem;">
                <li>Pastikan koneksi internet stabil</li>
                <li>Tidak ada user yang sedang login</li>
                <li>Backup database secara manual (opsional)</li>
            </ul>
        </div>
        <div>
            <h4 style="font-size: 0.95rem; margin-bottom: 0.5rem; color: var(--text-primary);"><i class="fas fa-undo" style="color: var(--neon-orange);"></i> Jika Gagal</h4>
            <ul style="list-style: disc; padding-left: 1.5rem; color: var(--text-muted); font-size: 0.9rem;">
                <li>Extract backup terakhir ke folder root</li>
                <li>Jalankan <code>composer install</code> (jika perlu)</li>
                <li>Hubungi support jika masih bermasalah</li>
            </ul>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
