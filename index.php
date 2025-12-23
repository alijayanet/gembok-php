<?php
/**
 * CodeIgniter 4.3.x front controller
 * 
 * Adapted for shared hosting deployment.
 * 
 * INSTRUKSI DEPLOY KE SHARED HOSTING:
 * 1. Upload folder aplikasi (app, vendor, writable, .env, install.php) ke folder
 *    di LUAR public_html, misalnya: /home/username/gembok/
 * 2. Upload file ini (index.php) dan .htaccess ke folder public_html
 * 3. Ubah baris ROOTPATH (line 22) menjadi: realpath(__DIR__ . '/../gembok')
 */

// Check PHP version.
$minPhpVersion = '8.0';
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    exit("Your PHP version must be {$minPhpVersion} or higher. Current: " . PHP_VERSION);
}

// Path to the front controller (this file)
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// PRODUCTION: Path to app folder (outside public_html)
// PRODUCTION: Path to app folder (outside public_html)
define('ROOTPATH', realpath(__DIR__ . '/gembok') . DIRECTORY_SEPARATOR);

// Ensure the current directory is pointing to the front controller's directory
chdir(FCPATH);

/*
 *---------------------------------------------------------------
 * BOOTSTRAP THE APPLICATION
 *---------------------------------------------------------------
 */

// Check if vendor folder exists
if (!file_exists(ROOTPATH . 'vendor/autoload.php')) {
    exit('<h1>Error: Vendor folder not found</h1>'
        . '<p>Pastikan folder vendor/ sudah di-upload atau jalankan composer install</p>');
}

// Load Composer autoloader
require ROOTPATH . 'vendor/autoload.php';

// Load our paths config file
require ROOTPATH . 'app/Config/Paths.php';

$paths = new Config\Paths();

// Set system directory
$paths->systemDirectory = ROOTPATH . 'vendor/codeigniter4/framework/system';
$paths->appDirectory = ROOTPATH . 'app';
$paths->writableDirectory = ROOTPATH . 'writable';
$paths->testsDirectory = ROOTPATH . 'tests';
$paths->viewDirectory = ROOTPATH . 'app/Views';

// Location of the framework bootstrap file.
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';

// Load environment settings from .env files
if (file_exists(ROOTPATH . '.env')) {
    require_once SYSTEMPATH . 'Config/DotEnv.php';
    (new CodeIgniter\Config\DotEnv(ROOTPATH))->load();
}

/*
 * ---------------------------------------------------------------
 * GRAB OUR CODEIGNITER INSTANCE
 * ---------------------------------------------------------------
 */

$app = Config\Services::codeigniter();
$app->initialize();
$context = is_cli() ? 'php-cli' : 'web';
$app->setContext($context);

/*
 *---------------------------------------------------------------
 * LAUNCH THE APPLICATION
 *---------------------------------------------------------------
 */

$app->run();
