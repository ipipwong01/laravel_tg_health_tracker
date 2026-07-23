<?php

namespace Tests\Unit;

use App\Services\Telegram\TelegramCommandParser;
use Tests\TestCase;

class TelegramCommandParserTest extends TestCase
{
    public function test_parses_glucose_with_context_and_note(): void
    {
        $parsed = app(TelegramCommandParser::class)->parse('/sugar 7.2 fasting felt dizzy');
        $this->assertSame('sugar', $parsed->name);
        $this->assertSame(7.2, $parsed->data['value']);
        $this->assertSame('fasting', $parsed->data['context']);
        $this->assertSame('felt dizzy', $parsed->data['notes']);
    }

    public function test_parses_mg_dl_and_bp(): void
    {
        $this->assertSame('mg/dL', app(TelegramCommandParser::class)->parse('/sugar 126 mg/dL before meal')->data['unit']);
        $bp = app(TelegramCommandParser::class)->parse('/bp 128 82 72 after walking');
        $this->assertSame(72, $bp->data['pulse']);
        $this->assertSame('after walking', $bp->data['notes']);
    }
}
