<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramHelper
{
    protected static $baseUrl = "https://api.telegram.org/bot";

    public static function sendMessage($chatId, $text, $keyboard = null)
    {
        $url = self::$baseUrl . env('TELEGRAM_BOT_TOKEN') . "/sendMessage";

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];

        if ($keyboard) {
            $payload['reply_markup'] = json_encode($keyboard);
        }

        return Http::post($url, $payload)->json();
    }

    public static function sendKeyboard($chatId, $text, $options)
    {
        $keyboard = [
            'inline_keyboard' => $options
        ];

        return self::sendMessage($chatId, $text, $keyboard);
    }
}
