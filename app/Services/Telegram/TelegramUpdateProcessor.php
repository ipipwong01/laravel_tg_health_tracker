<?php

namespace App\Services\Telegram;

use App\Actions\RecordBloodGlucose;
use App\Actions\RecordBloodPressure;
use App\Models\BloodGlucoseReading;
use App\Models\BloodPressureReading;
use App\Models\TelegramUpdate;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class TelegramUpdateProcessor
{
    public function __construct(private TelegramCommandParser $parser, private TelegramClient $telegram, private RecordBloodGlucose $recordGlucose, private RecordBloodPressure $recordPressure) {}

    public function process(array $update): void
    {
        $id = $update['update_id'] ?? null;
        if (! $id || TelegramUpdate::where('update_id', $id)->exists()) {
            return;
        }
        $message = $update['message'] ?? null;
        if (! $message || ! isset($message['text'],$message['from']['id'],$message['chat']['id'])) {
            TelegramUpdate::create(['update_id' => $id, 'processed_at' => now()]);

            return;
        }
        $chat = $message['chat'];
        $userId = (string) $message['from']['id'];
        $chatId = (int) $chat['id'];
        if (config('telegram.private_chats_only') && ($chat['type'] ?? '') !== 'private') {
            $this->finish($id);

            return;
        }
        if (! in_array($userId, config('telegram.allowed_user_ids'), true)) {
            if (config('telegram.reply_to_unauthorized')) {
                $this->telegram->sendMessage($chatId, 'Unauthorized.');
            } $this->finish($id);

            return;
        }
        try {
            $command = $this->parser->parse($message['text']);
            $reply = $this->handle($command->name, $command->data, (int) $userId, $chatId, $message['text']);
        } catch (InvalidArgumentException $e) {
            $reply = 'Invalid command. Try <code>/sugar 7.2 fasting</code> or <code>/bp 128 82 72</code>.';
        }
        $this->telegram->sendMessage($chatId, $reply);
        $this->finish($id);
    }

    private function finish(int $id): void
    {
        TelegramUpdate::firstOrCreate(['update_id' => $id], ['processed_at' => now()]);
    }

    private function handle(string $name, array $data, int $user, int $chat, string $original): string
    {
        if ($name === 'sugar') {
            $r = $this->recordGlucose->handle($user, $chat, $data, $original);

            return "✅ Blood sugar recorded\n\nValue: {$r->value} {$r->unit}\nContext: ".($r->measurement_context ? ucwords(str_replace('_', ' ', $r->measurement_context)) : 'Not specified')."\nRecorded: ".$r->measured_at->isoFormat('D MMMM YYYY, h:mm A');
        }
        if ($name === 'bp') {
            $r = $this->recordPressure->handle($user, $chat, $data, $original);

            return "✅ Blood pressure recorded\n\nBlood pressure: {$r->systolic}/{$r->diastolic} mmHg".($r->pulse ? "\nPulse: {$r->pulse} bpm" : '')."\nRecorded: ".$r->measured_at->isoFormat('D MMMM YYYY, h:mm A');
        }
        if (in_array($name, ['start', 'help'])) {
            return "Record: <code>/sugar 7.2 fasting</code>, <code>/bp 128 82 72</code>\nHistory: /latest, /today, /week\nDelete: /delete_last then /confirm_delete";
        }
        if ($name === 'latest') {
            return $this->latest($user);
        }
        if ($name === 'today') {
            return $this->today($user);
        }
        if ($name === 'week') {
            return $this->week($user);
        }
        if ($name === 'delete_last') {
            return $this->requestDelete($user);
        }
        if ($name === 'confirm_delete') {
            return $this->deleteLast($user);
        }

        return 'Unsupported command.';
    }

    private function latest(int $user): string
    {
        $g = BloodGlucoseReading::where('telegram_user_id', $user)->latest('measured_at')->first();
        $b = BloodPressureReading::where('telegram_user_id', $user)->latest('measured_at')->first();

        return "Latest readings\n\nSugar: ".($g ? "{$g->value} {$g->unit}" : 'none')."\nBlood pressure: ".($b ? "{$b->systolic}/{$b->diastolic} mmHg" : 'none');
    }

    private function today(int $user): string
    {
        $start = now()->startOfDay();
        $g = BloodGlucoseReading::where('telegram_user_id', $user)->where('measured_at', '>=', $start)->get();
        $b = BloodPressureReading::where('telegram_user_id', $user)->where('measured_at', '>=', $start)->get();

        return "Today's readings\nSugar: ".$g->map(fn ($r) => "{$r->value} {$r->unit}")->join(', ')."\nBlood pressure: ".$b->map(fn ($r) => "{$r->systolic}/{$r->diastolic}")->join(', ');
    }

    private function week(int $user): string
    {
        $g = BloodGlucoseReading::where('telegram_user_id', $user)->where('measured_at', '>=', now()->subDays(7))->get()->groupBy('unit');
        $b = BloodPressureReading::where('telegram_user_id', $user)->where('measured_at', '>=', now()->subDays(7))->get();
        $glucose = $g->map(fn ($rows, $unit) => "{$unit}: {$rows->count()} readings, min {$rows->min('value')}, max {$rows->max('value')}, avg ".round($rows->avg('value'), 2))->join("\n");

        return "7-day summary\n\nGlucose\n".($glucose ?: 'No readings')."\n\nBlood pressure: {$b->count()} readings".($b->isNotEmpty() ? ', average '.round($b->avg('systolic')).'/'.round($b->avg('diastolic')).' mmHg' : '');
    }

    private function requestDelete(int $user): string
    {
        $latest = $this->latestRecord($user);
        if (! $latest) {
            return 'There is no reading to delete.';
        } Cache::put("telegram-delete:{$user}", ['type' => $latest::class, 'id' => $latest->id], now()->addMinutes(10));
        $label = $latest instanceof BloodGlucoseReading ? "blood sugar {$latest->value} {$latest->unit}" : "blood pressure {$latest->systolic}/{$latest->diastolic} mmHg";

        return "Latest record: {$label}.\nReply with <code>/confirm_delete</code> within 10 minutes to permanently delete it.";
    }

    private function deleteLast(int $user): string
    {
        $pending = Cache::pull("telegram-delete:{$user}");
        if (! $pending) {
            return 'No pending deletion. Use /delete_last first.';
        } $record = $pending['type']::where('telegram_user_id', $user)->find($pending['id']);
        if (! $record) {
            return 'That record is no longer available.';
        } $record->delete();

        return 'Your selected reading was deleted.';
    }

    private function latestRecord(int $user): BloodGlucoseReading|BloodPressureReading|null
    {
        $g = BloodGlucoseReading::where('telegram_user_id', $user)->latest('measured_at')->first();
        $b = BloodPressureReading::where('telegram_user_id', $user)->latest('measured_at')->first();

        return collect([$g, $b])->filter()->sortByDesc('measured_at')->first();
    }
}
