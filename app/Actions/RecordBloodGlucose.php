<?php

namespace App\Actions;

use App\Models\BloodGlucoseReading;

class RecordBloodGlucose
{
    public function handle(int $userId, int $chatId, array $data, string $original): BloodGlucoseReading
    {
        return BloodGlucoseReading::create(['telegram_user_id' => $userId, 'telegram_chat_id' => $chatId, 'value' => $data['value'], 'unit' => $data['unit'], 'measurement_context' => $data['context'], 'notes' => $data['notes'], 'original_message' => $original, 'measured_at' => now()]);
    }
}
