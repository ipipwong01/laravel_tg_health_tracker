<?php

namespace App\ValueObjects;

class ParsedTelegramCommand
{
    public function __construct(public readonly string $name, public readonly array $data = []) {}
}
