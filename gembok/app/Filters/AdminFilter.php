<?php
namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

/**
 * Admin Authentication Filter
 * 
 * Protects admin routes from unauthorized access.
 * Requires user to be logged in with admin role.
 */
class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Get current URI path
        $uri = service('uri');
        $path = $uri->getPath();
        
        // Skip filter for login and logout routes
        if (preg_match('#^(admin/login|admin/logout)#i', $path)) {
            return null;
        }
        
        $session = session();
        
        // Check if user is logged in as admin
        if (!$session->has('admin_logged_in') || $session->get('admin_logged_in') !== true) {
            // Store intended URL for redirect after login
            $session->set('admin_redirect_url', current_url());
            
            // Redirect to admin login using site_url for proper path
            return redirect()->to(site_url('admin/login'))->with('error', 'Silakan login terlebih dahulu.');
        }
        
        // Check if user has admin role
        $userRole = $session->get('admin_role');
        
        // Block non-admin/technician roles from all admin routes
        if ($userRole !== null && !in_array($userRole, ['admin', 'superadmin', 'technician'])) {
            return redirect()->to(site_url('/'))->with('error', 'Anda tidak memiliki akses ke halaman ini.');
        }

        // Specific restrictions for Technicians
        if ($userRole === 'technician') {
            $restrictedPatterns = [
                '#^admin/analytics#i',
                '#^admin/mikrotik#i',
                '#^admin/hotspot#i',
                '#^admin/billing#i',
                '#^admin/technicians#i', // management list
                '#^admin/update#i',
                '#^admin/diagnostic#i',
                '#^admin/genieacs#i', // only allow /admin/technician/genieacs
                '#^admin/command#i', // terminal commands
                '#^admin/api/analytics#i',
                '#^admin/api/invoices#i',
                '#^admin/api/customers#i',
            ];

            foreach ($restrictedPatterns as $pattern) {
                if (preg_match($pattern, $path)) {
                    return redirect()->to(site_url('admin/dashboard'))->with('error', 'Akses dibatasi. Halaman ini hanya untuk Administrator.');
                }
            }
        }
        
        // Continue with request
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing needed
        return null;
    }
}

