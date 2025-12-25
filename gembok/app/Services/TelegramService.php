<?php
namespace App\Services;

class TelegramService
{
    private $botToken;
    private $apiUrl;

    public function __construct()
    {
        // Try to get token from ConfigService first, fallback to env
        $config = new \App\Services\ConfigService();
        $this->botToken = $config->get('TELEGRAM_BOT_TOKEN');
        
        // Fallback to environment variable if not in database
        if (empty($this->botToken)) {
            $this->botToken = getenv('TELEGRAM_BOT_TOKEN');
        }
        
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}/";
    }

    /**
     * Send text message
     */
    public function sendMessage($chatId, $text, $parseMode = 'Markdown', $replyMarkup = null)
    {
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode
        ];

        if ($replyMarkup) {
            $data['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->apiRequest('sendMessage', $data);
    }

    /**
     * Edit message text
     */
    public function editMessage($chatId, $messageId, $text, $parseMode = 'Markdown', $replyMarkup = null)
    {
        $data = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => $parseMode
        ];

        if ($replyMarkup) {
            $data['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->apiRequest('editMessageText', $data);
    }

    /**
     * Answer callback query (prevent loading spinner)
     */
    public function answerCallback($callbackQueryId, $text = '', $showAlert = false)
    {
        $data = [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
            'show_alert' => $showAlert
        ];

        return $this->apiRequest('answerCallbackQuery', $data);
    }

    /**
     * Create inline keyboard button
     */
    public function inlineButton($text, $callbackData)
    {
        return ['text' => $text, 'callback_data' => $callbackData];
    }

    /**
     * Create inline keyboard markup
     */
    public function inlineKeyboard($buttons)
    {
        return ['inline_keyboard' => $buttons];
    }

    /**
     * Make API request to Telegram
     */
    private function apiRequest($method, $data)
    {
        $url = $this->apiUrl . $method;
        
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ],
        ];

        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        
        return json_decode($result, true);
    }

    /**
     * Set webhook URL
     */
    public function setWebhook($webhookUrl)
    {
        return $this->apiRequest('setWebhook', [
            'url' => $webhookUrl
        ]);
    }

    /**
     * Get webhook info
     */
    public function getWebhookInfo()
    {
        return $this->apiRequest('getWebhookInfo', []);
    }

    /**
     * Delete webhook
     */
    public function deleteWebhook()
    {
        return $this->apiRequest('deleteWebhook', []);
    }
}
