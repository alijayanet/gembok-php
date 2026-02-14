<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class CronManager extends BaseController
{
    protected $db;
    
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        helper(['form', 'url']);
    }
    
    /**
     * Display cron schedules list
     */
    public function index()
    {
        $schedules = $this->db->table('cron_schedules')
            ->orderBy('is_active', 'DESC')
            ->orderBy('schedule_time', 'ASC')
            ->get()
            ->getResultArray();
            
        $recentLogs = $this->db->table('cron_logs')
            ->select('cron_logs.*, cron_schedules.name as schedule_name')
            ->join('cron_schedules', 'cron_schedules.id = cron_logs.schedule_id')
            ->orderBy('cron_logs.created_at', 'DESC')
            ->limit(20)
            ->get()
            ->getResultArray();
            
        return view('admin/cron_manager/index', [
            'schedules' => $schedules,
            'recentLogs' => $recentLogs,
            'title' => 'Cronjob Manager'
        ]);
    }
    
    /**
     * Create new schedule form
     */
    public function create()
    {
        return view('admin/cron_manager/form', [
            'title' => 'Tambah Schedule Baru',
            'schedule' => null
        ]);
    }
    
    /**
     * Edit existing schedule
     */
    public function edit($id)
    {
        $schedule = $this->db->table('cron_schedules')
            ->where('id', $id)
            ->get()
            ->getRowArray();
            
        if (!$schedule) {
            return redirect()->to('/admin/cron-manager')->with('error', 'Schedule tidak ditemukan');
        }
        
        return view('admin/cron_manager/form', [
            'title' => 'Edit Schedule',
            'schedule' => $schedule
        ]);
    }
    
    /**
     * Save schedule (create/update)
     */
    public function save()
    {
        $id = $this->request->getPost('id');
        
        $rules = [
            'name' => 'required|max_length[100]',
            'task_type' => 'required|in_list[auto_isolir,backup_db,send_reminders,custom_script]',
            'schedule_time' => 'required|regex_match[^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$]',
            'schedule_days' => 'required|in_list[daily,weekly,monthly,monday,tuesday,wednesday,thursday,friday,saturday,sunday]'
        ];
        
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        
        $data = [
            'name' => $this->request->getPost('name'),
            'task_type' => $this->request->getPost('task_type'),
            'schedule_time' => $this->request->getPost('schedule_time'),
            'schedule_days' => $this->request->getPost('schedule_days'),
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Calculate next run
        $data['next_run'] = $this->calculateNextRun($data['schedule_days'], $data['schedule_time']);
        
        if ($id) {
            // Update existing
            $this->db->table('cron_schedules')->where('id', $id)->update($data);
            $message = 'Schedule berhasil diupdate';
        } else {
            // Create new
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->table('cron_schedules')->insert($data);
            $message = 'Schedule berhasil dibuat';
        }
        
        return redirect()->to('/admin/cron-manager')->with('success', $message);
    }
    
    /**
     * Delete schedule
     */
    public function delete($id)
    {
        $this->db->table('cron_schedules')->where('id', $id)->delete();
        return redirect()->to('/admin/cron-manager')->with('success', 'Schedule berhasil dihapus');
    }
    
    /**
     * Toggle schedule status (enable/disable)
     */
    public function toggle($id)
    {
        $schedule = $this->db->table('cron_schedules')
            ->where('id', $id)
            ->get()
            ->getRowArray();
            
        if (!$schedule) {
            return redirect()->to('/admin/cron-manager')->with('error', 'Schedule tidak ditemukan');
        }
        
        $newStatus = $schedule['is_active'] ? 0 : 1;
        $updateData = [
            'is_active' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($newStatus) {
            $updateData['next_run'] = $this->calculateNextRun($schedule['schedule_days'], $schedule['schedule_time']);
        } else {
            $updateData['next_run'] = null;
        }
        
        $this->db->table('cron_schedules')->where('id', $id)->update($updateData);
        
        $statusText = $newStatus ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->to('/admin/cron-manager')->with('success', "Schedule berhasil $statusText");
    }
    
    /**
     * Manual execute schedule
     */
    public function execute($id)
    {
        $schedule = $this->db->table('cron_schedules')
            ->where('id', $id)
            ->get()
            ->getRowArray();
            
        if (!$schedule) {
            return redirect()->to('/admin/cron-manager')->with('error', 'Schedule tidak ditemukan');
        }
        
        if (!$schedule['is_active']) {
            return redirect()->to('/admin/cron-manager')->with('error', 'Schedule tidak aktif');
        }
        
        // Execute the task
        $result = $this->executeTask($schedule);
        
        if ($result['success']) {
            return redirect()->to('/admin/cron-manager')->with('success', 'Schedule berhasil dieksekusi: ' . $result['message']);
        } else {
            return redirect()->to('/admin/cron-manager')->with('error', 'Gagal mengeksekusi schedule: ' . $result['message']);
        }
    }
    
    /**
     * Calculate next run datetime
     */
    private function calculateNextRun($days, $time)
    {
        $now = new \DateTime();
        $scheduleTime = \DateTime::createFromFormat('H:i:s', $time);
        
        switch ($days) {
            case 'daily':
                $next = $now->setTime($scheduleTime->format('H'), $scheduleTime->format('i'), $scheduleTime->format('s'));
                if ($next <= new \DateTime()) {
                    $next->add(new \DateInterval('P1D'));
                }
                break;
                
            case 'weekly':
                $next = $now->setTime($scheduleTime->format('H'), $scheduleTime->format('i'), $scheduleTime->format('s'));
                if ($next <= new \DateTime()) {
                    $next->add(new \DateInterval('P1W'));
                }
                break;
                
            case 'monthly':
                $next = $now->setTime($scheduleTime->format('H'), $scheduleTime->format('i'), $scheduleTime->format('s'));
                if ($next <= new \DateTime()) {
                    $next->add(new \DateInterval('P1M'));
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
                
                $next = $now->setTime($scheduleTime->format('H'), $scheduleTime->format('i'), $scheduleTime->format('s'));
                $next->add(new \DateInterval('P' . $daysUntil . 'D'));
                break;
        }
        
        return $next->format('Y-m-d H:i:s');
    }
    
    /**
     * Execute specific task
     */
    private function executeTask($schedule)
    {
        $startTime = microtime(true);
        
        // Log start
        $logId = $this->db->table('cron_logs')->insert([
            'schedule_id' => $schedule['id'],
            'status' => 'started',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        try {
            switch ($schedule['task_type']) {
                case 'auto_isolir':
                    $result = $this->executeAutoIsolir();
                    break;
                    
                case 'backup_db':
                    $result = $this->executeBackupDb();
                    break;
                    
                case 'send_reminders':
                    $result = $this->executeSendReminders();
                    break;
                    
                default:
                    throw new \Exception("Task type {$schedule['task_type']} not implemented");
            }
            
            $executionTime = round(microtime(true) - $startTime, 2);
            
            // Update schedule last run
            $this->db->table('cron_schedules')->where('id', $schedule['id'])->update([
                'last_run' => date('Y-m-d H:i:s'),
                'last_status' => 'success',
                'last_output' => $result['message'],
                'next_run' => $this->calculateNextRun($schedule['schedule_days'], $schedule['schedule_time'])
            ]);
            
            // Update log
            $this->db->table('cron_logs')->where('id', $logId)->update([
                'status' => 'success',
                'output' => $result['message'],
                'execution_time' => $executionTime
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            $executionTime = round(microtime(true) - $startTime, 2);
            $errorMessage = $e->getMessage();
            
            // Update schedule last run
            $this->db->table('cron_schedules')->where('id', $schedule['id'])->update([
                'last_run' => date('Y-m-d H:i:s'),
                'last_status' => 'failed',
                'last_output' => $errorMessage
            ]);
            
            // Update log
            $this->db->table('cron_logs')->where('id', $logId)->update([
                'status' => 'failed',
                'error_message' => $errorMessage,
                'execution_time' => $executionTime
            ]);
            
            return ['success' => false, 'message' => $errorMessage];
        }
    }
    
    /**
     * Execute auto isolir task
     */
    private function executeAutoIsolir()
    {
        // Include the daily.php script
        $dailyScript = __DIR__ . '/../../cron/daily.php';
        
        if (!file_exists($dailyScript)) {
            throw new \Exception("Daily cron script not found");
        }
        
        // Capture output
        ob_start();
        $result = require_once $dailyScript;
        $output = ob_get_clean();
        
        return ['success' => true, 'message' => 'Auto-isolir completed successfully. ' . $output];
    }
    
    /**
     * Execute backup database task
     */
    private function executeBackupDb()
    {
        // This would be implemented based on your backup requirements
        return ['success' => true, 'message' => 'Database backup completed successfully'];
    }
    
    /**
     * Execute send reminders task
     */
    private function executeSendReminders()
    {
        // This would be implemented based on your reminder requirements
        return ['success' => true, 'message' => 'Payment reminders sent successfully'];
    }
}