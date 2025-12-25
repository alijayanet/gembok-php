<?php
namespace App\Services;

use App\Services\ConfigService;

class TripayService
{
    private $apiKey;
    private $privateKey;
    private $merchantCode;
    private $mode; // 'sandbox' or 'production'
    private $baseUrl;
    private ConfigService $config;

    public function __construct()
    {
        $this->config = new ConfigService();
        
        $this->apiKey = $this->config->get('TRIPAY_API_KEY');
        $this->privateKey = $this->config->get('TRIPAY_PRIVATE_KEY');
        $this->merchantCode = $this->config->get('TRIPAY_MERCHANT_CODE');
        $this->mode = $this->config->get('TRIPAY_MODE') ?: 'sandbox';

        $this->baseUrl = ($this->mode === 'production') 
            ? 'https://tripay.co.id/api/' 
            : 'https://tripay.co.id/api-sandbox/';
    }

    /**
     * Get Payment Channels
     */
    public function getChannels()
    {
        $payload = ['code' => null];
        return $this->request('merchant/payment-channel', $payload, 'GET');
    }

    /**
     * Create Transaction
     */
    public function createTransaction($invoiceData)
    {
        /*
        $invoiceData structure required:
        - method (payment channel code e.g., BRIVA)
        - merchant_ref (invoice number unique)
        - amount (integer)
        - customer_name
        - customer_email
        - customer_phone
        - order_items (array of items)
        - return_url (redirect after payment)
        */

        $data = [
            'method'         => $invoiceData['method'],
            'merchant_ref'   => $invoiceData['merchant_ref'],
            'amount'         => $invoiceData['amount'],
            'customer_name'  => $invoiceData['customer_name'],
            'customer_email' => $invoiceData['customer_email'],
            'customer_phone' => $invoiceData['customer_phone'],
            'order_items'    => $invoiceData['order_items'],
            'callback_url'   => base_url('webhook/payment'), // Auto-set webhook URL
            'return_url'     => $invoiceData['return_url'],
            'expired_time'   => (time() + (24 * 60 * 60)), // 24 hours expiry
            'signature'      => $this->createSignature($invoiceData['merchant_ref'], $invoiceData['amount'])
        ];

        return $this->request('transaction/create', $data, 'POST');
    }
    
    /**
     * Get Transaction Detail
     */
    public function detailTransaction($reference)
    {
        $payload = ['reference' => $reference];
        return $this->request('transaction/detail', $payload, 'GET');
    }

    /**
     * Create Signature for Transaction Request
     */
    private function createSignature($merchantRef, $amount)
    {
        return hash_hmac('sha256', $this->merchantCode . $merchantRef . $amount, $this->privateKey);
    }
    
    /**
     * Validate Incoming Callback Signature
     */
    public function validateCallback($jsonBody, $signature)
    {
        $calculatedSignature = hash_hmac('sha256', $jsonBody, $this->privateKey);
        return hash_equals($signature, $calculatedSignature);
    }

    /**
     * Send Request to Tripay API
     */
    private function request($endpoint, $payload = [], $method = 'GET')
    {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey
        ];

        $curl = curl_init();

        if ($method === 'GET') {
            if (!empty($payload)) {
                $url .= '?' . http_build_query($payload);
            }
        } elseif ($method === 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($payload)); // x-www-form-urlencoded usually preferred by Tripay docs but check docs
            // Tripay usually accepts form-data or raw json depending on endpoint. 
            // Standard create transaction uses form-data or urlencoded usually. 
            // Let's stick to http_build_query for standard POST fields.
        }

        curl_setopt_array($curl, [
            CURLOPT_FRESH_CONNECT  => true,
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_FAILONERROR    => false,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
            CURLOPT_SSL_VERIFYPEER => true, // Enabled for production security
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            return ['success' => false, 'message' => 'CURL Error: ' . $error];
        }

        return json_decode($response, true);
    }
}
