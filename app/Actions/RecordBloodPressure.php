<?php

namespace App\Actions;

use App\Models\BloodPressureReading;

class RecordBloodPressure
{
    public function handle(int $userId, int $chatId, array $data, string $original): BloodPressureReading
    {
        return BloodPressureReading::create(['telegram_user_id' => $userId, 'telegram_chat_id' => $chatId, 'systolic' => $data['systolic'], 'diastolic' => $data['diastolic'], 'pulse' => $data['pulse'], 'notes' => $data['notes'], 'original_message' => $original, 'measured_at' => now()]);
    }
}
