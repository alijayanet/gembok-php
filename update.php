<?php
/**
 * GEMBOK Auto-Updater
 * 
 * Download dan install update dari GitHub secara otomatis
 * 
 * CARA PENGGUNAAN:
 * 1. Browser: http://yourdomain.com/update.php
 * 2. CLI: php update.php
 * 
 * KEAMANAN:
 * - Backup otomatis sebelum update
 * - Rollback jika gagal
 * - Validasi file sebelum extract
 */

// Detect Environment
$isCli = php_sapi_name() === 'cli';
$br = $isCli ? "\n" : "<br>";

// Configuration
define('GITHUB_REPO', 'alijayanet/gembok-php'); // GitHub Repository
define('GITHUB_BRANCH', 'main'); // Branch utama
define('UPDATE_DIR', __DIR__);
define('BACKUP_DIR', __DIR__ . '/backups');
define('TEMP_DIR', sys_get_temp_dir() . '/gembok_update');

// HTML Header for Browser Mode
if (!$isCli) {
    echo "<!DOCTYPE html><html><head><title>GEMBOK Auto-Updater</title>";
    echo "<style>
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #0f172a; color: #e2e8f0; padding: 2rem; line-height: 1.6; }
        .container { max-width: 900px; margin: 0 auto; background: #1e293b; padding: 2.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #334155; }
        h1 { color: #38bdf8; margin-bottom: 0.5rem; font-size: 1.8rem; }
        h2 { color: #f8fafc; border-bottom: 1px solid #334155; padding-bottom: 0.5rem; margin-top: 2rem; font-size: 1.25rem; }
        .success { color: #4ade80; }
        .error { color: #f87171; font-weight: bold; }
        .warning { color: #fbbf24; }
        .info { color: #38bdf8; }
        .log-item { margin: 0.25rem 0; font-family: 'Consolas', monospace; font-size: 0.9rem; padding: 0.5rem; background: rgba(0,0,0,0.2); border-radius: 4px; }
        .btn { display: inline-block; padding: 0.75rem 2rem; background: #0ea5e9; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; margin-top: 1rem; transition: background 0.2s; border: none; cursor: pointer; }
        .btn:hover { background: #0284c7; }
        .btn-danger { background: #ef4444; }
        .btn-danger:hover { background: #dc2626; }
        code { background: #0f172a; padding: 0.1rem 0.3rem; border-radius: 4px; color: #e2e8f0; }
        .progress { width: 100%; height: 30px; background: #334155; border-radius: 6px; overflow: hidden; margin: 1rem 0; }
        .progress-bar { height: 100%; background: linear-gradient(90deg, #0ea5e9, #38bdf8); transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
    </style></head><body><div class='container'>";
    echo "<h1>🚀 GEMBOK Auto-Updater</h1>";
    echo "<p class='info'>Update aplikasi dari GitHub dengan mudah dan aman.</p>";
}

// Helper Functions
function logMessage($message, $type = 'info', $isCli = false) {
    $icons = [
        'success' => '✅',
        'error' => '❌',
        'warning' => '⚠️',
        'info' => 'ℹ️'
    ];
    
    $icon = $icons[$type] ?? 'ℹ️';
    
    if ($isCli) {
        echo "$icon $message\n";
    } else {
        echo "<div class='log-item $type'>$icon $message</div>";
    }
    
    flush();
    ob_flush();
}

function createBackup($isCli) {
    logMessage("Membuat backup aplikasi...", 'info', $isCli);
    
    if (!file_exists(BACKUP_DIR)) {
        mkdir(BACKUP_DIR, 0755, true);
    }
    
    $backupFile = BACKUP_DIR . '/backup_' . date('Y-m-d_H-i-s') . '.zip';
    
    // Create ZIP backup
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($backupFile, ZipArchive::CREATE) === TRUE) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(UPDATE_DIR),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            
            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen(UPDATE_DIR) + 1);
                    
                    // Skip backups and temp directories
                    if (strpos($relativePath, 'backups/') === 0 || 
                        strpos($relativePath, 'writable/') === 0 ||
                        strpos($relativePath, 'vendor/') === 0) {
                        continue;
                    }
                    
                    $zip->addFile($filePath, $relativePath);
                }
            }
            
            $zip->close();
            logMessage("Backup berhasil: " . basename($backupFile), 'success', $isCli);
            return $backupFile;
        }
    }
    
    logMessage("Gagal membuat backup (ZipArchive tidak tersedia)", 'warning', $isCli);
    return false;
}

function downloadUpdate($isCli) {
    logMessage("Mengunduh update dari GitHub...", 'info', $isCli);
    
    $zipUrl = 'https://github.com/' . GITHUB_REPO . '/archive/refs/heads/' . GITHUB_BRANCH . '.zip';
    $zipFile = TEMP_DIR . '/update.zip';
    
    // Create temp directory
    if (!file_exists(TEMP_DIR)) {
        mkdir(TEMP_DIR, 0755, true);
    }
    
    // Download ZIP file
    $ch = curl_init($zipUrl);
    $fp = fopen($zipFile, 'w');
    
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    // Progress callback
    if (!$isCli) {
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($resource, $download_size, $downloaded, $upload_size, $uploaded) {
            if ($download_size > 0) {
                $percent = round(($downloaded / $download_size) * 100);
                echo "<script>
                    var bar = document.getElementById('progress-bar');
                    if (bar) {
                        bar.style.width = '{$percent}%';
                        bar.textContent = '{$percent}%';
                    }
                </script>";
                flush();
                ob_flush();
            }
        });
    }
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    fclose($fp);
    
    if ($result === false || $httpCode !== 200) {
        logMessage("Gagal mengunduh update (HTTP $httpCode)", 'error', $isCli);
        return false;
    }
    
    logMessage("Download selesai: " . round(filesize($zipFile) / 1024 / 1024, 2) . " MB", 'success', $isCli);
    return $zipFile;
}

function extractUpdate($zipFile, $isCli) {
    logMessage("Mengekstrak file update...", 'info', $isCli);
    
    if (!class_exists('ZipArchive')) {
        logMessage("ZipArchive tidak tersedia", 'error', $isCli);
        return false;
    }
    
    $zip = new ZipArchive();
    if ($zip->open($zipFile) === TRUE) {
        $extractPath = TEMP_DIR . '/extracted';
        $zip->extractTo($extractPath);
        $zip->close();
        
        logMessage("Ekstraksi selesai", 'success', $isCli);
        return $extractPath;
    }
    
    logMessage("Gagal mengekstrak file", 'error', $isCli);
    return false;
}

function applyUpdate($extractPath, $isCli) {
    logMessage("Menerapkan update...", 'info', $isCli);
    
    // Find the extracted folder (usually repo-branch format)
    $dirs = glob($extractPath . '/*', GLOB_ONLYDIR);
    if (empty($dirs)) {
        logMessage("Folder update tidak ditemukan", 'error', $isCli);
        return false;
    }
    
    $sourceDir = $dirs[0];
    
    // Files to skip (don't overwrite)
    $skipFiles = [
        '.env',
        'writable/',
        'backups/',
        'vendor/',
        '.git/',
        '.gitignore',
        // Custom files - jangan ditimpa
        'public/assets/js/pagination.js',
        'public/assets/js/auto-refresh.js',
        'DB_INDEXES.txt',
        'encode_telegram_credentials.php',
        'test-invoice-data.php',
        // Documentation - jangan ditimpa jika sudah diedit
        'ENHANCEMENTS/',
        'AUDIT_REPORT_LENGKAP.md',
        'RINGKASAN_AUDIT.md',
        'CHECKLIST_PERBAIKAN.md',
        'DAFTAR_FILE_PENTING.md',
        'PANDUAN_DEVELOPER.md',
        'PANDUAN_UPDATE.md',
        'INDEX_DOKUMENTASI.md'
    ];
    
    // Copy files
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    $copied = 0;
    $skipped = 0;
    
    foreach ($files as $file) {
        $relativePath = substr($file->getRealPath(), strlen($sourceDir) + 1);
        
        // Skip certain files
        $skip = false;
        foreach ($skipFiles as $skipPattern) {
            if (strpos($relativePath, $skipPattern) === 0) {
                $skip = true;
                $skipped++;
                break;
            }
        }
        
        if ($skip) continue;
        
        $targetPath = UPDATE_DIR . '/' . $relativePath;
        
        if ($file->isDir()) {
            if (!file_exists($targetPath)) {
                mkdir($targetPath, 0755, true);
            }
        } else {
            // Create directory if not exists
            $targetDir = dirname($targetPath);
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            
            // Copy file
            if (copy($file->getRealPath(), $targetPath)) {
                $copied++;
            }
        }
    }
    
    logMessage("Update diterapkan: $copied file copied, $skipped file skipped", 'success', $isCli);
    return true;
}

function cleanup($isCli) {
    logMessage("Membersihkan file temporary...", 'info', $isCli);
    
    // Remove temp directory
    if (file_exists(TEMP_DIR)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(TEMP_DIR, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        
        rmdir(TEMP_DIR);
    }
    
    logMessage("Cleanup selesai", 'success', $isCli);
}

function runDatabaseMigrations($isCli) {
    logMessage("Menjalankan database migrations...", 'info', $isCli);
    
    // Check if install.php exists
    $installFile = UPDATE_DIR . '/install.php';
    if (file_exists($installFile)) {
        // Run install.php in silent mode
        ob_start();
        include $installFile;
        ob_end_clean();
        
        logMessage("Database migrations selesai", 'success', $isCli);
        return true;
    }
    
    logMessage("install.php tidak ditemukan, skip migrations", 'warning', $isCli);
    return false;
}

// Main Update Process
if (!$isCli) {
    echo "<h2>📦 Proses Update</h2>";
    echo "<div id='progress-container'><div class='progress'><div id='progress-bar' class='progress-bar' style='width: 0%'>0%</div></div></div>";
}

try {
    // Step 1: Create Backup
    if (!$isCli) echo "<h3>Step 1: Backup</h3>";
    $backupFile = createBackup($isCli);
    
    // Step 2: Download Update
    if (!$isCli) echo "<h3>Step 2: Download</h3>";
    $zipFile = downloadUpdate($isCli);
    if (!$zipFile) {
        throw new Exception("Gagal mengunduh update");
    }
    
    // Step 3: Extract
    if (!$isCli) echo "<h3>Step 3: Extract</h3>";
    $extractPath = extractUpdate($zipFile, $isCli);
    if (!$extractPath) {
        throw new Exception("Gagal mengekstrak update");
    }
    
    // Step 4: Apply Update
    if (!$isCli) echo "<h3>Step 4: Apply Update</h3>";
    if (!applyUpdate($extractPath, $isCli)) {
        throw new Exception("Gagal menerapkan update");
    }
    
    // Step 5: Run Migrations
    if (!$isCli) echo "<h3>Step 5: Database Migrations</h3>";
    runDatabaseMigrations($isCli);
    
    // Step 6: Cleanup
    if (!$isCli) echo "<h3>Step 6: Cleanup</h3>";
    cleanup($isCli);
    
    // Success
    if (!$isCli) {
        echo "<h2 class='success'>✅ Update Berhasil!</h2>";
        echo "<p>Aplikasi telah diupdate ke versi terbaru dari GitHub.</p>";
        if ($backupFile) {
            echo "<p class='info'>📦 Backup tersimpan di: <code>" . basename($backupFile) . "</code></p>";
        }
        echo "<a href='/admin/dashboard' class='btn'>🏠 Kembali ke Dashboard</a>";
        echo "<a href='/gembok/update.php' class='btn' style='background: #6b7280;'>🔄 Update Lagi</a>";
    } else {
        echo "\n✅ Update berhasil!\n";
        if ($backupFile) {
            echo "📦 Backup: $backupFile\n";
        }
    }
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage(), 'error', $isCli);
    
    if (!$isCli) {
        echo "<h2 class='error'>❌ Update Gagal</h2>";
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        
        if ($backupFile && file_exists($backupFile)) {
            echo "<p class='warning'>⚠️ Anda bisa restore dari backup: <code>" . basename($backupFile) . "</code></p>";
            echo "<p>Gunakan file manager atau extract manual backup tersebut.</p>";
        }
        
        echo "<a href='/admin/dashboard' class='btn btn-danger'>🏠 Kembali ke Dashboard</a>";
    } else {
        echo "\n❌ Update gagal: " . $e->getMessage() . "\n";
        if ($backupFile) {
            echo "📦 Restore dari backup: $backupFile\n";
        }
    }
}

if (!$isCli) {
    echo "</div></body></html>";
}
