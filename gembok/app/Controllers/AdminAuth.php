<?php
namespace App\Controllers;

use App\Controllers\BaseController;

/**
 * AdminAuth Controller
 * 
 * Handles admin authentication (login/logout)
 */
class AdminAuth extends BaseController
{
    /**
     * Display admin login form
     */
    public function login()
    {
        // If already logged in, redirect to dashboard
        $session = session();
        if ($session->get('admin_logged_in') === true) {
            return redirect()->to('/admin');
        }
        
        return view('admin/login');
    }

    /**
     * Process admin login
     */
    public function authenticate()
    {
        $session = session();
        $db = \Config\Database::connect();
        
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        
        // Validate input
        if (empty($username) || empty($password)) {
            return redirect()->back()->with('error', 'Username dan password wajib diisi.');
        }
        
        // Find user in database - check username and role (admin, superadmin, or technician)
        $user = $db->table('users')
                   ->where('username', $username)
                   ->groupStart()
                       ->where('role', 'admin')
                       ->orWhere('role', 'superadmin')
                       ->orWhere('role', 'technician')
                   ->groupEnd()
                   ->get()
                   ->getRowArray();
        
        if (!$user) {
            return redirect()->back()->with('error', 'Username tidak ditemukan atau Anda tidak memiliki akses admin/teknisi.');
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return redirect()->back()->with('error', 'Password salah.');
        }
        
        // Check if user is active
        if (isset($user['is_active']) && !$user['is_active']) {
            return redirect()->back()->with('error', 'Akun Anda dinonaktifkan.');
        }
        
        // Set session
        $session->set([
            'admin_logged_in' => true,
            'admin_id'        => $user['id'],
            'admin_username'  => $user['username'],
            'admin_name'      => $user['name'],
            'admin_role'      => $user['role'],
        ]);
        
        // Update last login
        $db->table('users')
           ->where('id', $user['id'])
           ->update(['last_login' => date('Y-m-d H:i:s')]);
        
        // Determine default dashboard based on role
        $defaultDashboard = ($user['role'] === 'technician') ? '/admin/technician/dashboard' : '/admin';
        
        // Redirect to intended URL or dashboard
        $redirectUrl = $session->get('admin_redirect_url') ?? $defaultDashboard;
        $session->remove('admin_redirect_url');
        
        return redirect()->to($redirectUrl)->with('success', 'Selamat datang, ' . $user['name'] . '!');
    }

    /**
     * Admin logout
     */
    public function logout()
    {
        $session = session();
        
        // Remove admin session data
        $session->remove([
            'admin_logged_in',
            'admin_id',
            'admin_username',
            'admin_name',
            'admin_role',
        ]);
        
        return redirect()->to('/admin/login')->with('success', 'Anda telah logout.');
    }
}
