<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BloodPressureReading extends Model
{
    protected $fillable = ['telegram_user_id', 'telegram_chat_id', 'systolic', 'diastolic', 'pulse', 'measured_at', 'notes', 'original_message'];

    protected function casts(): array
    {
        return ['measured_at' => 'datetime'];
    }
}
