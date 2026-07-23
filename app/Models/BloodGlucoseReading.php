<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BloodGlucoseReading extends Model
{
    protected $fillable = ['telegram_user_id', 'telegram_chat_id', 'value', 'unit', 'measurement_context', 'measured_at', 'notes', 'original_message'];

    protected function casts(): array
    {
        return ['value' => 'decimal:2', 'measured_at' => 'datetime'];
    }
}
