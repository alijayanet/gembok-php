<?php
namespace App\Controllers;

use App\Services\TripayService;
use App\Services\MikrotikService;

class Webhook extends BaseController
{
    public function payment()
    {
        // 1. Get Incoming Data
        $json = file_get_contents('php://input');
        $callbackSignature = $this->request->getHeaderLine('X-Callback-Signature');
        
        // Log incoming webhook for debugging
        log_message('info', 'Tripay Webhook Received: ' . $json);

        // 2. Validate Signature
        $tripay = new TripayService();
        if (!$tripay->validateCallback($json, $callbackSignature)) {
            log_message('error', 'Invalid Tripay Signature');
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Invalid signature']);
        }

        $data = json_decode($json, true);
        if (!$data) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Invalid JSON']);
        }

        // 3. Process Payment Status
        $merchantRef = $data['merchant_ref']; // This should be our invoice number
        $status = $data['status']; // PAID, EXPIRED, FAILED, REFUND
        
        /*
         Tripay Callback Data Structure example:
         {
           "reference": "DEV-T123456789...",
           "merchant_ref": "INV-2025-001",
           "payment_method": "BRIVA",
           "payment_method_code": "BRIVA",
           "total_amount": 100000,
           "fee_merchant": 0,
           "fee_customer": 1000,
           "total_fee": 1000,
           "amount_received": 100000,
           "is_closed_payment": 1,
           "status": "PAID",
           "paid_at": 164...
           ...
         }
        */

        if ($status === 'PAID') {
            $this->handlePaidInvoice($merchantRef, $data);
        } elseif ($status === 'EXPIRED' || $status === 'FAILED') {
            $this->handleFailedInvoice($merchantRef, $status);
        }

        return $this->response->setJSON(['success' => true]);
    }

    private function handlePaidInvoice($invoiceNumber, $paymentData)
    {
        $db = \Config\Database::connect();
        
        // 1. Update Invoice Status
        $invoice = $db->table('invoices')->getWhere(['invoice_number' => $invoiceNumber])->getRowArray();
        
        if (!$invoice) {
            log_message('error', "Invoice not found: {$invoiceNumber}");
            return;
        }

        // Update invoice
        $db->table('invoices')->where('id', $invoice['id'])->update([
            'status' => 'paid',
            'paid_at' => date('Y-m-d H:i:s'),
            'payment_method' => $paymentData['payment_method'] ?? 'Tripay',
            'payment_ref' => $paymentData['reference'] ?? '',
        ]);
        
        log_message('info', "Invoice {$invoiceNumber} marked as PAID.");

        // 2. Auto-Activate Service Logic (Un-isolir)
        // Check if all due invoices for this customer are paid
        $customerId = $invoice['customer_id'];
        
        // Get Customer
        $customer = $db->table('customers')->where('id', $customerId)->get()->getRowArray();
        
        if ($customer && $customer['status'] === 'isolated') {
            // Check any other unpaid overdue invoices
            $unpaidCount = $db->table('invoices')
                ->where('customer_id', $customerId)
                ->where('status', 'unpaid')
                ->where('due_date <', date('Y-m-d'))
                ->countAllResults();
                
            if ($unpaidCount === 0) {
                // Restore Connection
                $this->unisolateCustomer($customer);
            }
        }
    }
    
    private function handleFailedInvoice($invoiceNumber, $status)
    {
        $db = \Config\Database::connect();
        // Maybe log or update status if you track cancelled invoices
        // Usually we keep them as 'unpaid' until regenerate or just mark logs
        log_message('info', "Invoice {$invoiceNumber} status: {$status}");
    }

    private function unisolateCustomer($customer)
    {
        $db = \Config\Database::connect();
        $mikrotik = new MikrotikService();
        
        // Get package for normal profile
        $package = $db->table('packages')->where('id', $customer['package_id'])->get()->getRowArray();
        
        if ($package && !empty($package['profile']) && !empty($customer['pppoe_username'])) {
            // Restore MikroTik Profile
            $result = $mikrotik->setPppoeUserProfile($customer['pppoe_username'], $package['profile']);
            
            if ($result) {
                // Update DB Status
                $db->table('customers')->where('id', $customer['id'])->update([
                    'status' => 'active',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                log_message('info', "Customer UN-ISOLATED: {$customer['name']} ({$customer['pppoe_username']})");
            } else {
                log_message('error', "Failed to un-isolate customer Mikrotik: {$customer['pppoe_username']}");
            }
        }
    }
    
    // Placeholder for WhatsApp webhook
    public function whatsapp()
    {
        // Handle incoming WhatsApp message hooks here (Fonnte/WA Gateway)
        return $this->response->setJSON(['status' => 'ok']);
    }
    
    // Placeholder for Midtrans webhook
    public function midtrans()
    {
        // Handle Midtrans callback logic
        return $this->response->setJSON(['status' => 'ok']);
    }
}
