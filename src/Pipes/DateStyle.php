<?php

declare(strict_types=1);

namespace Medas\Templates\Pipes;

// The style names a template author writes, mapped onto IntlDateFormatter's
// constants. Shared by the date and datetime pipes, so both accept the same words.
final class DateStyle
{
    public static function fromName(string $style): int
    {
        return match ($style) {
            'short' => \IntlDateFormatter::SHORT,
            'medium' => \IntlDateFormatter::MEDIUM,
            'long' => \IntlDateFormatter::LONG,
            'full' => \IntlDateFormatter::FULL,
            default => throw new \InvalidArgumentException(sprintf(
                'Unknown date or time style "%s": use short, medium, long or full.',
                $style,
            )),
        };
    }
}
