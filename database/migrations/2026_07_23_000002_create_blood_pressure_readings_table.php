<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blood_pressure_readings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('telegram_user_id');
            $table->unsignedBigInteger('telegram_chat_id');
            $table->unsignedSmallInteger('systolic');
            $table->unsignedSmallInteger('diastolic');
            $table->unsignedSmallInteger('pulse')->nullable();
            $table->timestamp('measured_at');
            $table->text('notes')->nullable();
            $table->text('original_message')->nullable();
            $table->timestamps();
            $table->index(['telegram_user_id', 'measured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_pressure_readings');
    }
};
