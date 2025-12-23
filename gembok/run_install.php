<?php
/**
 * run_install.php – Script satu kali untuk migrasi database.
 * 
 * INSTRUKSI:
 * 1. Upload file ini ke folder aplikasi (bersama install.php)
 * 2. Akses via browser: https://yourdomain.com/gembok/run_install.php
 *    atau: https://yourdomain.com/run_install.php (jika di public_html)
 * 3. Setelah berhasil, HAPUS file ini segera!
 * 
 * PERINGATAN: File ini harus dihapus setelah instalasi selesai
 * untuk mencegah eksekusi ulang oleh pihak tidak berwenang.
 */

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Content-Type: text/html; charset=UTF-8');

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Gembok Installer</title>';
echo '<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;background:#1a1a2e;color:#eee}';
echo 'h1{color:#00d9ff}pre{background:#0d0d1a;padding:15px;border-radius:8px;overflow-x:auto}';
echo '.success{color:#00ff88}.error{color:#ff4757}.warning{color:#ffa502}</style></head><body>';
echo '<h1>🔧 Gembok Database Installer</h1>';

// -------------------------------------------------
// 1. Check if vendor folder exists
// -------------------------------------------------
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo '<p class="error">❌ Error: Folder <code>vendor/</code> tidak ditemukan!</p>';
    echo '<p>Pastikan folder <code>vendor/</code> sudah di-upload bersama file aplikasi.</p>';
    echo '</body></html>';
    exit;
}

require __DIR__ . '/vendor/autoload.php';

// -------------------------------------------------
// 2. Load environment variables
// -------------------------------------------------
if (!file_exists(__DIR__ . '/.env')) {
    echo '<p class="error">❌ Error: File <code>.env</code> tidak ditemukan!</p>';
    echo '<p>Buat file <code>.env</code> dengan konfigurasi database Anda.</p>';
    echo '</body></html>';
    exit;
}

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    echo '<p class="success">✅ File .env berhasil dimuat</p>';
} catch (Exception $e) {
    echo '<p class="error">❌ Error membaca .env: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</body></html>';
    exit;
}

// -------------------------------------------------
// 3. Run the installation script
// -------------------------------------------------
echo '<h2>📦 Menjalankan Migrasi Database...</h2>';
echo '<pre>';

// Capture output from install.php
ob_start();
try {
    require __DIR__ . '/install.php';
    $output = ob_get_clean();
    echo htmlspecialchars($output);
} catch (Exception $e) {
    ob_end_clean();
    echo '</pre>';
    echo '<p class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</body></html>';
    exit;
}

echo '</pre>';

// -------------------------------------------------
// 4. Success message
// -------------------------------------------------
echo '<h2 class="success">✅ Instalasi Selesai!</h2>';
echo '<div style="background:#0d0d1a;padding:20px;border-radius:8px;border-left:4px solid #ff4757">';
echo '<h3 class="warning">⚠️ PENTING: Hapus File Ini Sekarang!</h3>';
echo '<p>Untuk keamanan, hapus file <code>run_install.php</code> dari server segera.</p>';
echo '<p>Langkah selanjutnya:</p>';
echo '<ol>';
echo '<li>Hapus file <code>run_install.php</code> via File Manager atau FTP</li>';
echo '<li>Akses aplikasi Anda di: <a href="/" style="color:#00d9ff">Homepage</a></li>';
echo '<li>Uji API: <a href="/api/onuLocations" style="color:#00d9ff">/api/onuLocations</a></li>';
echo '</ol>';
echo '</div>';

echo '</body></html>';
