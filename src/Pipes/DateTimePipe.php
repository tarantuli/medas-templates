<?php

declare(strict_types=1);

namespace Medas\Templates\Pipes;

use Medas\Core\Attributes\Service;
use Medas\Templates\VariablePipe;

// Formats a moment as a localised date and time: {{ order:createdAt | datetime }}
// gives "31 mrt 2026, 14:05" in nl-NL. The date style (default medium) and time style
// (default short) are set positionally - {{ createdAt | datetime:long:medium }} - each
// one of short, medium, long or full. The time is shown in the value's own time zone.
// For a date alone, use the date pipe.
#[Service]
readonly class DateTimePipe implements VariablePipe
{
    public function name(): string
    {
        return 'datetime';
    }

    public function apply(
        mixed  $value,
        string $locale,
        string $dateStyle = 'medium',
        string $timeStyle = 'short'
    ): string
    {
        if (!$value instanceof \DateTimeInterface) {
            throw new \InvalidArgumentException(sprintf(
                'The datetime pipe expects a DateTimeInterface, got %s.',
                get_debug_type($value),
            ));
        }

        $formatter = new \IntlDateFormatter(
            $locale,
            DateStyle::fromName($dateStyle),
            DateStyle::fromName($timeStyle),
            $value->getTimezone(),
        );

        return $formatter->format($value);
    }
}
