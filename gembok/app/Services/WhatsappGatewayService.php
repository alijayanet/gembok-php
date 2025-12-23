<?php
namespace App\Services;

/**
 * WhatsappGatewayService
 * -------------------------------------------------
 * Unified wrapper for several popular WhatsApp gateway APIs
 * (Fonnte, MPWA, Wablas, etc.).
 *
 * The gateway to use is defined by the environment variable
 * `WHATSAPP_GATEWAY`. Supported values:
 *   - fonnte
 *   - mpwa
 *   - wablas
 *
 * Each provider requires its own token / key which must be set
 * in the .env file (see the generated .env example).
 */
class WhatsappGatewayService
{
    /** @var string selected gateway */
    private string $gateway;

    public function __construct()
    {
        $this->gateway = strtolower(getenv('WHATSAPP_GATEWAY') ?: 'fonnte');
    }

    /**
     * Send a text message to a phone number.
     *
     * @param string $to   Phone number in international format (e.g. 628123456789)
     * @param string $text Message body
     * @return array       ['success'=>bool,'response'=>mixed]
     */
    public function sendMessage(string $to, string $text): array
    {
        switch ($this->gateway) {
            case 'mpwa':
                return $this->sendViaMpwa($to, $text);
            case 'wablas':
                return $this->sendViaWablas($to, $text);
            case 'fonnte':
            default:
                return $this->sendViaFonnte($to, $text);
        }
    }

    /** -------------------------------------------------
     *  FONNTE implementation
     */
    private function sendViaFonnte(string $to, string $text): array
    {
        $url   = getenv('FONNTE_API_URL') ?: 'https://api.fonnte.com/send';
        $token = getenv('FONNTE_TOKEN');
        $payload = [
            'target' => $to,
            'message' => $text,
        ];
        return $this->curlPost($url, $payload, ["Authorization: $token"]);
    }

    /** -------------------------------------------------
     *  MPWA implementation (https://mpwa.id)
     */
    private function sendViaMpwa(string $to, string $text): array
    {
        $url   = getenv('MPWA_API_URL') ?: 'https://gateway.mpwa.id/api/v1/message/text';
        $token = getenv('MPWA_TOKEN');
        $payload = [
            'to'      => $to,
            'message' => $text,
        ];
        return $this->curlPost($url, $payload, ["Authorization: Bearer $token"]);
    }

    /** -------------------------------------------------
     *  WABLAS implementation (https://wablas.com)
     */
    private function sendViaWablas(string $to, string $text): array
    {
        $url   = getenv('WABLAS_API_URL') ?: 'https://console.wablas.com/api/v2/sendMessage';
        $token = getenv('WABLAS_TOKEN');
        $payload = [
            'phone'   => $to,
            'message' => $text,
        ];
        return $this->curlPost($url, $payload, ["Authorization: $token"]);
    }

    /** -------------------------------------------------
     *  Helper: generic cURL POST with JSON body.
     */
    private function curlPost(string $url, array $payload, array $headers = []): array
    {
        $ch = curl_init($url);
        $defaultHeaders = [
            'Content-Type: application/json',
        ];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => array_merge($defaultHeaders, $headers),
            CURLOPT_TIMEOUT        => 15,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);
        if ($error) {
            return ['success' => false, 'response' => $error];
        }
        $decoded = json_decode($response, true);
        $success = $httpCode >= 200 && $httpCode < 300;
        return ['success' => $success, 'response' => $decoded ?? $response];
    }
}
?>
