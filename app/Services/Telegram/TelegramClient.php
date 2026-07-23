<?php

namespace App\Services\Telegram;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramClient
{
    private function request(): PendingRequest
    {
        $token = config('telegram.token');
        if (! $token) {
            throw new RuntimeException('Telegram token is not configured.');
        }

        return Http::baseUrl("https://api.telegram.org/bot{$token}")->acceptJson()->timeout(config('telegram.poll_timeout') + 10)->retry(2, 500);
    }

    private function call(string $method, array $data = []): mixed
    {
        $response = $this->request()->post($method, $data);
        if (! $response->successful() || ! $response->json('ok')) {
            $description = $response->json('description');

            throw new RuntimeException('Telegram API request failed'.($description ? ': '.$description : '.'));
        }

        return $response->json('result');
    }

    public function getMe(): array
    {
        return $this->call('getMe');
    }

    public function getUpdates(?int $offset): array
    {
        return $this->call('getUpdates', array_filter(['offset' => $offset, 'timeout' => config('telegram.poll_timeout'), 'allowed_updates' => ['message']]));
    }

    public function sendMessage(int $chatId, string $text): array
    {
        return $this->call('sendMessage', ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'HTML']);
    }

    public function setMyCommands(array $commands): bool
    {
        $this->call('setMyCommands', ['commands' => $commands]);

        return true;
    }
}
