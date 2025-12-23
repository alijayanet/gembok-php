<?php
namespace App\Controllers;

use CodeIgniter\Controller;

class Settings extends BaseController
{
    public function __construct()
    {
        helper(['url']);
    }

    /**
     * Integration Settings Page
     * Display webhook URLs and integration information
     */
    public function integrations()
    {
        // Check if user is logged in
        if (!session()->get('logged_in')) {
            return redirect()->to(base_url('login'));
        }

        // Get base URL from current request
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = "{$protocol}://{$host}";
        
        // Webhook URLs
        $webhooks = [
            'whatsapp' => [
                'title' => 'WhatsApp Webhook',
                'url' => "{$baseUrl}/webhook/whatsapp",
                'method' => 'POST',
                'description' => 'Paste this URL to your WhatsApp Gateway (Fonnte, WA Gateway, etc.)',
                'icon' => 'fab fa-whatsapp',
                'color' => '#25D366'
            ],
            'payment' => [
                'title' => 'Payment Webhook (Tripay)',
                'url' => "{$baseUrl}/webhook/payment",
                'method' => 'POST',
                'description' => 'Configure this as callback URL in Tripay dashboard',
                'icon' => 'fas fa-money-bill-wave',
                'color' => '#00C9A7'
            ],
            'midtrans' => [
                'title' => 'Payment Webhook (Midtrans)',
                'url' => "{$baseUrl}/webhook/midtrans",
                'method' => 'POST',
                'description' => 'Configure this as notification URL in Midtrans dashboard',
                'icon' => 'fas fa-credit-card',
                'color' => '#FF6B6B'
            ]
        ];

        // API Endpoints
        $apiEndpoints = [
            'customers' => [
                'title' => 'Customers API',
                'url' => "{$baseUrl}/api/customers",
                'method' => 'GET/POST',
                'description' => 'Manage customers via API',
                'icon' => 'fas fa-users'
            ],
            'invoices' => [
                'title' => 'Invoices API',
                'url' => "{$baseUrl}/api/invoices",
                'method' => 'GET/POST',
                'description' => 'Manage invoices via API',
                'icon' => 'fas fa-file-invoice'
            ],
            'packages' => [
                'title' => 'Packages API',
                'url' => "{$baseUrl}/api/packages",
                'method' => 'GET',
                'description' => 'Get package list',
                'icon' => 'fas fa-box'
            ]
        ];

        $data = [
            'title' => 'Integration Settings',
            'webhooks' => $webhooks,
            'apiEndpoints' => $apiEndpoints,
            'baseUrl' => $baseUrl
        ];

        return view('admin/settings/integrations', $data);
    }
}
