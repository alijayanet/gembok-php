<?php
namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        if (!$session->has('customer_id')) {
            // Not logged in – redirect to login page
            return redirect()->to('/login');
        }
        // otherwise continue
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post‑processing needed
    }
}
?>
