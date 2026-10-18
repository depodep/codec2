<?php

namespace App\Services;

use App\Notifications\TelegramOutboundNotification;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Throwable;

class TelegramBotService
{
    public function enabled(): bool
    {
        return (bool) config('services.telegram.enabled', false);
    }

    public function configured(): bool
    {
        return $this->enabled() && filled(config('services.telegram.bot_token'));
    }

    public function webhookSecret(): string
    {
        return (string) config('services.telegram.webhook_secret', '');
    }

    public function sendMessage(string $chatId, string $text, ?array $keyboard = null): void
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Telegram bot is disabled.');
        }

        if (! $this->configured()) {
            throw new RuntimeException('Telegram bot token is not configured.');
        }

        try {
            Notification::route('telegram', $chatId)
                ->notify(new TelegramOutboundNotification($chatId, $text, $keyboard));
        } catch (Throwable $exception) {
            Log::warning('Telegram notification delivery failed.', [
                'exception' => $exception::class,
            ]);
            throw new RuntimeException('Telegram notification delivery failed.');
        }
    }

    public function answerCallback(string $callbackId, string $text = ''): void
    {
        $this->call('answerCallbackQuery', [
            'callback_query_id' => $callbackId,
            'text' => $text,
        ]);
    }

    public function getMe(): array
    {
        return $this->call('getMe');
    }

    public function setWebhook(string $url): void
    {
        $secret = $this->webhookSecret();
        if ($secret === '') {
            throw new RuntimeException('Telegram webhook secret is not configured.');
        }

        $this->call('setWebhook', [
            'url' => $url,
            'secret_token' => $secret,
            'allowed_updates' => ['message', 'callback_query'],
        ]);
    }

    private function call(string $method, array $parameters = []): array
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Telegram bot is disabled.');
        }

        $token = (string) config('services.telegram.bot_token', '');
        if ($token === '') {
            throw new RuntimeException('Telegram bot token is not configured.');
        }

        try {
            $response = Http::acceptJson()
                ->timeout(10)
                ->post("https://api.telegram.org/bot{$token}/{$method}", $parameters);
        } catch (ConnectionException) {
            Log::warning('Telegram API connection failed.', ['method' => $method]);
            throw new RuntimeException('Telegram API connection failed.');
        }

        $result = $response->json();
        if (! $response->successful() || ! is_array($result) || ! ($result['ok'] ?? false)) {
            Log::warning('Telegram API request failed.', [
                'method' => $method,
                'http_status' => $response->status(),
                'telegram_error_code' => is_array($result) ? ($result['error_code'] ?? null) : null,
            ]);
            throw new RuntimeException('Telegram API request failed.');
        }

        return is_array($result['result'] ?? null) ? $result['result'] : [];
    }
}
