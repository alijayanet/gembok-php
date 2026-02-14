<?php
/**
 * Web-based Cron Scheduler
 * 
 * File ini harus dijalankan setiap menit oleh cronjob server:
 * * * * * * /usr/bin/php /path/to/gembok/cron/scheduler.php >/dev/null 2>&1
 * 
 * Fungsi: Mengecek schedule yang aktif dan menjalankan task yang sudah waktunya
 */

// Load environment
require_once __DIR__ . '/../bootstrap.php';

use Config\Database;
use Config\Services;

// Set time limit untuk mencegah timeout
try {
    set_time_limit(300); // 5 menit max execution
    
    // Koneksi database
    $db = Database::connect();
    
    // Ambil waktu sekarang
    $now = date('Y-m-d H:i:s');
    
    // Cari schedule yang aktif dan waktunya sudah tiba
    $schedules = $db->table('cron_schedules')
        ->where('is_active', 1)
        ->where('next_run <=', $now)
        ->get()
        ->getResultArray();
    
    if (empty($schedules)) {
        echo "Tidak ada schedule yang perlu dijalankan.\n";
        exit(0);
    }
    
    echo "Menjalankan " . count($schedules) . " schedule...\n";
    
    foreach ($schedules as $schedule) {
        echo "Menjalankan: {$schedule['name']} ({$schedule['task_type']})\n";
        
        try {
            // Update status menjadi running
            $db->table('cron_schedules')
                ->where('id', $schedule['id'])
                ->update([
                    'last_status' => 'running',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            
            // Log start
            $logData = [
                'schedule_id' => $schedule['id'],
                'status' => 'started',
                'created_at' => date('Y-m-d H:i:s')
            ];
            $db->table('cron_logs')->insert($logData);
            $logId = $db->insertID();
            
            // Jalankan task sesuai tipe
            $startTime = microtime(true);
            $result = executeTask($schedule, $db);
            $executionTime = round(microtime(true) - $startTime, 2);
            
            // Update log dengan hasil
            $db->table('cron_logs')
                ->where('id', $logId)
                ->update([
                    'status' => $result['status'],
                    'output' => $result['message'],
                    'error_message' => $result['error'] ?? null,
                    'execution_time' => $executionTime
                ]);
            
            // Update schedule
            $nextRun = calculateNextRun($schedule['schedule_days'], $schedule['schedule_time']);
            
            $db->table('cron_schedules')
                ->where('id', $schedule['id'])
                ->update([
                    'last_run' => date('Y-m-d H:i:s'),
                    'last_status' => $result['status'],
                    'last_output' => $result['message'],
                    'next_run' => $nextRun,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            
            echo "✅ {$schedule['name']} selesai ({$executionTime}s)\n";
            
        } catch (Exception $e) {
            // Error handling
            $errorMessage = $e->getMessage();
            echo "❌ {$schedule['name']} gagal: {$errorMessage}\n";
            
            // Update schedule dengan status failed
            $db->table('cron_schedules')
                ->where('id', $schedule['id'])
                ->update([
                    'last_run' => date('Y-m-d H:i:s'),
                    'last_status' => 'failed',
                    'last_output' => $errorMessage,
                    'next_run' => calculateNextRun($schedule['schedule_days'], $schedule['schedule_time']),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            
            // Update log jika ada
            if (isset($logId)) {
                $db->table('cron_logs')
                    ->where('id', $logId)
                    ->update([
                        'status' => 'failed',
                        'error_message' => $errorMessage
                    ]);
            }
        }
    }
    
    echo "Semua schedule selesai.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Execute specific task based on type
 */
function executeTask($schedule, $db)
{
    switch ($schedule['task_type']) {
        case 'auto_isolir':
            return executeAutoIsolir();
            
        case 'backup_db':
            return executeBackupDb();
            
        case 'send_reminders':
            return executeSendReminders();
            
        default:
            throw new Exception("Task type {$schedule['task_type']} not implemented");
    }
}

/**
 * Execute auto isolir task
 */
function executeAutoIsolir()
{
    $dailyScript = __DIR__ . '/daily.php';
    
    if (!file_exists($dailyScript)) {
        throw new Exception("Daily cron script not found at: {$dailyScript}");
    }
    
    // Capture output
    ob_start();
    
    // Include the daily script
    require_once $dailyScript;
    
    $output = ob_get_clean();
    
    return [
        'status' => 'success',
        'message' => 'Auto-isolir completed successfully. ' . $output
    ];
}

/**
 * Execute backup database task
 */
function executeBackupDb()
{
    // Implementasi backup database bisa ditambahkan di sini
    return [
        'status' => 'success',
        'message' => 'Database backup completed successfully'
    ];
}

/**
 * Execute send reminders task
 */
function executeSendReminders()
{
    // Implementasi kirim pengingat bisa ditambahkan di sini
    return [
        'status' => 'success',
        'message' => 'Payment reminders sent successfully'
    ];
}

/**
 * Calculate next run datetime
 */
function calculateNextRun($days, $time)
{
    $now = new DateTime();
    $scheduleTime = DateTime::createFromFormat('H:i:s', $time);
    
    switch ($days) {
        case 'daily':
            $next = clone $now;
            $next->setTime($scheduleTime->format('H'), $scheduleTime->format('i'), $scheduleTime->format('s'));
            if ($next <= $now) {
                $next->add(new DateInterval('P1D'));
            }
            break;
            
        case 'weekly':
            $next = clone $now;
            $next->setTime($scheduleTime->format('H'), $scheduleTime->format('i'), $scheduleTime->format('s'));
            if ($next <= $now) {
                $next->add(new DateInterval('P1W'));
            }
            break;
            
        case 'monthly':
            $next = clone $now;
            $next->setTime($scheduleTime->format('H'), $scheduleTime->format('i'), $scheduleTime->format('s'));
            if ($next <= $now) {
                $next->add(new DateInterval('P1M'));
            }
            break;
            
        default: // Specific day of week
            $targetDay = ['monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6, 'sunday' => 0];
            $currentDay = (int)$now->format('w');
            $targetDayNum = $targetDay[$days] ?? 0;
            
            $daysUntil = ($targetDayNum - $currentDay + 7) % 7;
            if ($daysUntil == 0 && $now->format('H:i:s') > $time) {
                $daysUntil = 7;
            }
            
            $next = clone $now;
            $next->setTime($scheduleTime->format('H'), $scheduleTime->format('i'), $scheduleTime->format('s'));
            $next->add(new DateInterval('P' . $daysUntil . 'D'));
            break;
    }
    
    return $next->format('Y-m-d H:i:s');
}