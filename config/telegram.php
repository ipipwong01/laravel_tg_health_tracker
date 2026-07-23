<?php

return [
    'token' => env('TELEGRAM_BOT_TOKEN'),
    'allowed_user_ids' => array_values(array_filter(array_map('trim', explode(',', (string) env('TELEGRAM_ALLOWED_USER_IDS', ''))))),
    'poll_timeout' => (int) env('TELEGRAM_POLL_TIMEOUT', 30),
    'default_glucose_unit' => env('TELEGRAM_DEFAULT_GLUCOSE_UNIT', 'mmol/L'),
    'reply_to_unauthorized' => (bool) env('TELEGRAM_REPLY_TO_UNAUTHORIZED', true),
    'private_chats_only' => (bool) env('TELEGRAM_PRIVATE_CHATS_ONLY', true),
    'glucose_range' => ['min' => (float) env('TELEGRAM_GLUCOSE_MIN', 1), 'max' => (float) env('TELEGRAM_GLUCOSE_MAX', 1000)],
    'bp_range' => ['systolic_min' => 40, 'systolic_max' => 300, 'diastolic_min' => 20, 'diastolic_max' => 200, 'pulse_min' => 20, 'pulse_max' => 250],
];
