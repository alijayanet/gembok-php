<?php
namespace App\Controllers;

use App\Models\CustomerModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

class Auth extends Controller
{
    public function login()
    {
        // Show login form
        return view('portal/login');
    }

    public function authenticate()
    {
        $request = $this->request;
        $idPelanggan = $request->getPost('id_pelanggan');
        $password    = $request->getPost('password');

        if (!$idPelanggan || !$password) {
            return view('portal/login', ['error' => 'Mohon isi ID Pelanggan dan Password']);
        }

        $customerModel = new CustomerModel();
        // Check by ID (Assuming pppoe_username is used as ID, or we can use phone as ID too, but usually it's unique ID)
        // Let's assume 'pppoe_username' is the unique ID for simplicity or add a new column 'customer_number'.
        // For now, let's use pppoe_username as the 'ID Pelanggan' for login.
        $customer = $customerModel->where('pppoe_username', $idPelanggan)->first();

        if (!$customer) {
            // Try phone just in case
            $customer = $customerModel->where('phone', $idPelanggan)->first();
        }
        
        if (!$customer) {
            return view('portal/login', ['error' => 'ID Pelanggan tidak ditemukan']);
        }

        // Verify Password
        // If password col is empty (migration), allow any password ONCE and update it? 
        // Or default password is phone number?
        
        $dbPassword = $customer['portal_password'] ?? '';
        
        if (empty($dbPassword)) {
            // First time login logic:
            // Check if input equals '1234' (Default Password)
            if ($password === '1234') {
                // Correct default password
            } else {
                return view('portal/login', ['error' => 'Password salah. Password default adalah 1234']);
            }
        } else {
            // Check hashed password
            if (!password_verify($password, $dbPassword)) {
                return view('portal/login', ['error' => 'Password salah']);
            }
        }

        // Set session
        $session = session();
        $session->set([
            'customer_id' => $customer['id'],
            'customer_phone' => $customer['phone'],
            'customer_name' => $customer['name'],
            'logged_in' => true
        ]);
        
        // Redirect to portal dashboard
        return redirect()->to('/portal');
    }

    public function logout()
    {
        $session = session();
        $session->destroy();
        return redirect()->to('/login');
    }
}
?>
