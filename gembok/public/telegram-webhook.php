<?php
/**
 * Telegram Webhook Direct Handler
 * Bypass CodeIgniter routing for immediate testing
 */

// Load CodeIgniter bootstrap
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
require_once dirname(__DIR__) . '/app/Config/Paths.php';
$paths = new Config\Paths();
require_once $paths->systemDirectory . '/bootstrap.php';

// Get webhook data
$input = file_get_contents('php://input');
$update = json_decode($input, true);

// If GET request (testing)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    echo json_encode([
        'ok' => true,
        'message' => 'Telegram webhook is ready!',
        'endpoint' => $_SERVER['REQUEST_URI'],
        'method' => $_SERVER['REQUEST_METHOD'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// If no data, return error
if (!$update) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'No data received']);
    exit;
}

// Load services
require_once dirname(__DIR__) . '/app/Services/TelegramService.php';
require_once dirname(__DIR__) . '/app/Services/MikrotikService.php';
require_once dirname(__DIR__) . '/app/Services/ConfigService.php';

use App\Services\TelegramService;
use App\Services\MikrotikService;

$telegram = new TelegramService();
$mikrotik = new MikrotikService();

// Handle message
if (isset($update['message'])) {
    $chatId = $update['message']['chat']['id'];
    $text = $update['message']['text'] ?? '';
    
    // Simple test response
    if (strtoupper($text) === '/START' || strtoupper($text) === 'PING') {
        $msg = "✅ *WEBHOOK WORKING!*\n\n";
        $msg .= "Bot connected successfully!\n";
        $msg .= "Time: " . date('Y-m-d H:i:s');
        
        $telegram->sendMessage($chatId, $msg);
    }
}

// Return success
header('Content-Type: application/json');
echo json_encode(['ok' => true]);
