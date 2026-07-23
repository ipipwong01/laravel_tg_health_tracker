<?php

namespace App\Console\Commands;

use App\Models\TelegramUpdate;
use App\Services\Telegram\TelegramClient;
use App\Services\Telegram\TelegramUpdateProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class TelegramPollCommand extends Command
{
    protected $signature = 'telegram:poll';

    protected $description = 'Continuously receive Telegram updates using long polling.';

    public function handle(TelegramClient $client, TelegramUpdateProcessor $processor): int
    {
        $lock = Cache::lock('telegram-poll-worker', 60);
        if (! $lock->get()) {
            $this->error('Another polling worker is already running.');

            return self::FAILURE;
        } try {
            while (true) {
                try {
                    $offset = ((int) TelegramUpdate::max('update_id')) + 1;
                    foreach ($client->getUpdates($offset) as $update) {
                        $processor->process($update);
                    }
                } catch (Throwable $e) {
                    report($e);
                    sleep(2);
                }
            }
        } finally {
            $lock->release();
        }
    }
}
