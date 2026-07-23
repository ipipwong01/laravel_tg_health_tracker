<?php

namespace App\Services\Telegram;

use App\ValueObjects\ParsedTelegramCommand;
use InvalidArgumentException;

class TelegramCommandParser
{
    public function parse(string $text): ParsedTelegramCommand
    {
        $parts = preg_split('/\s+/', trim($text), 2);
        $command = strtolower(preg_replace('/@[^\s]+/', '', $parts[0] ?? ''));
        $args = trim($parts[1] ?? '');

        return match ($command) {
            '/sugar' => new ParsedTelegramCommand('sugar', $this->sugar($args)),
            '/bp' => new ParsedTelegramCommand('bp', $this->bp($args)),
            '/start','/help','/latest','/today','/week','/delete_last','/confirm_delete' => new ParsedTelegramCommand(substr($command, 1)),
            default => throw new InvalidArgumentException('Unknown command.'),
        };
    }

    private function sugar(string $args): array
    {
        if (! preg_match('/^([0-9]+(?:\.[0-9]+)?)(?:\s+(mmol\/L|mg\/dL))?(?:\s+(.*))?$/i', $args, $m)) {
            throw new InvalidArgumentException('Format: /sugar 7.2 fasting');
        }
        $rest = trim($m[3] ?? '');
        $context = null;
        foreach (['before meal', 'after meal', 'fasting', 'bedtime', 'random'] as $candidate) {
            if (str_starts_with(strtolower($rest), $candidate)) {
                $context = str_replace(' ', '_', $candidate);
                $rest = trim(substr($rest, strlen($candidate)));
                break;
            }
        }
        $value = (float) $m[1];
        $range = config('telegram.glucose_range');
        if ($value < $range['min'] || $value > $range['max']) {
            throw new InvalidArgumentException('That glucose value is outside the configured recording range.');
        }

        return ['value' => $value, 'unit' => $m[2] ?? config('telegram.default_glucose_unit'), 'context' => $context, 'notes' => $rest ?: null];
    }

    private function bp(string $args): array
    {
        if (! preg_match('/^(\d+)\s+(\d+)(?:\s+(\d+))?(?:\s+(.*))?$/', $args, $m)) {
            throw new InvalidArgumentException('Format: /bp 128 82 [pulse] [notes]');
        }
        $r = config('telegram.bp_range');
        foreach (['systolic' => [$m[1], $r['systolic_min'], $r['systolic_max']], 'diastolic' => [$m[2], $r['diastolic_min'], $r['diastolic_max']]] as [$value,$min,$max]) {
            if ($value < $min || $value > $max) {
                throw new InvalidArgumentException('That blood pressure value is outside the configured recording range.');
            }
        }
        if (isset($m[3]) && $m[3] !== '' && ($m[3] < $r['pulse_min'] || $m[3] > $r['pulse_max'])) {
            throw new InvalidArgumentException('That pulse value is outside the configured recording range.');
        }

        return ['systolic' => (int) $m[1], 'diastolic' => (int) $m[2], 'pulse' => isset($m[3]) && $m[3] !== '' ? (int) $m[3] : null, 'notes' => trim($m[4] ?? '') ?: null];
    }
}
