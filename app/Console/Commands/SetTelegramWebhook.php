<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;
use RuntimeException;

class SetTelegramWebhook extends Command
{
    protected $signature = 'telegram:webhook {url : Public HTTPS URL for the Telegram webhook}';

    protected $description = 'Register the SIATRACK Telegram webhook with Telegram';

    public function handle(TelegramBotService $bot): int
    {
        $url = (string) $this->argument('url');
        $parts = parse_url($url);
        if (! filter_var($url, FILTER_VALIDATE_URL) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
            || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            $this->error('The webhook URL must be a valid public HTTPS URL.');

            return self::FAILURE;
        }

        try {
            $bot->setWebhook($url);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Telegram webhook registered successfully.');

        return self::SUCCESS;
    }
}
