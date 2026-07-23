<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramClient;
use Illuminate\Console\Command;
use Throwable;

class TelegramSetMenuCommand extends Command
{
    protected $signature = 'telegram:set-menu';

    protected $description = 'Publish the Telegram bot command menu.';

    public function handle(TelegramClient $client): int
    {
        try {
            $client->setMyCommands([['command' => 'start', 'description' => 'Show a quick introduction'], ['command' => 'help', 'description' => 'Show command examples'], ['command' => 'sugar', 'description' => 'Record blood sugar'], ['command' => 'bp', 'description' => 'Record blood pressure'], ['command' => 'latest', 'description' => 'Show the latest readings'], ['command' => 'today', 'description' => "Show today's readings"], ['command' => 'week', 'description' => 'Show the seven-day summary'], ['command' => 'delete_last', 'description' => 'Delete the latest reading']]);
            $this->info('Telegram command menu published.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Could not publish the Telegram command menu: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
