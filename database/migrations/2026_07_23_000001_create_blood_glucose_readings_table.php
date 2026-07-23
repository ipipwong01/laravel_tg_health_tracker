<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blood_glucose_readings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('telegram_user_id');
            $table->unsignedBigInteger('telegram_chat_id');
            $table->decimal('value', 8, 2);
            $table->string('unit', 10);
            $table->string('measurement_context', 30)->nullable();
            $table->timestamp('measured_at');
            $table->text('notes')->nullable();
            $table->text('original_message')->nullable();
            $table->timestamps();
            $table->index(['telegram_user_id', 'measured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_glucose_readings');
    }
};
