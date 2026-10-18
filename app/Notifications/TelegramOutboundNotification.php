<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

class TelegramOutboundNotification extends Notification
{
    public function __construct(
        public readonly string $chatId,
        public readonly string $text,
        public readonly ?array $keyboard = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['telegram'];
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        $message = TelegramMessage::create($this->text)
            ->to($this->chatId)
            ->normal()
            ->options(['disable_web_page_preview' => true]);

        if ($this->keyboard !== null) {
            $message->keyboardMarkup(['inline_keyboard' => $this->keyboard]);
        }

        return $message;
    }
}
