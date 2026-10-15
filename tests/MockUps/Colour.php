<?php

declare(strict_types=1);

namespace Medas\TemplatesTest\MockUps;

use Medas\Templates\Interfaces\HasLabel;

enum Colour: int implements HasLabel
{
    case Red = 1;
    case Green = 2;

    public function label(string $locale): string
    {
        return match ([$this, $locale]) {
            [self::Red, 'nl-NL'] => 'rood',
            [self::Green, 'nl-NL'] => 'groen',
            [self::Red, 'en-GB'] => 'red',
            [self::Green, 'en-GB'] => 'green',
        };
    }
}
