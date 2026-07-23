<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramClient;
use Illuminate\Console\Command;
use Throwable;

class TelegramTestCommand extends Command
{
    protected $signature = 'telegram:test';

    protected $description = 'Verify Telegram bot credentials.';

    public function handle(TelegramClient $client): int
    {
        try {
            $bot = $client->getMe();
            $this->info("Connected as @{$bot['username']}.");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Telegram connection failed. Check TELEGRAM_BOT_TOKEN.');

            return self::FAILURE;
        }
    }
}
