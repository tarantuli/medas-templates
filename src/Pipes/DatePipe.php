<?php

declare(strict_types=1);

namespace Medas\Templates\Pipes;

use Medas\Core\{Attributes\Service, Date};
use Medas\Templates\VariablePipe;

// Formats a date as a localised date string: 2026-03-31 becomes "31 mrt 2026" in
// nl-NL and "31 Mar 2026" in en-GB. The style defaults to medium and can be set
// positionally - {{ subscription:startDate | date:long }} - to short, medium, long or
// full. Like the currency pipe, it exists because a date has no safe locale-neutral
// rendering, so the author must ask for one.
//
// It takes a Date, or the date part of a DateTimeInterface in that value's own time
// zone; the datetime pipe shows the time as well.
#[Service]
readonly class DatePipe implements VariablePipe
{
    public function name(): string
    {
        return 'date';
    }

    public function apply(mixed $value, string $locale, string $style = 'medium'): string
    {
        $moment = match (true) {
            $value instanceof Date
                => new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $value->year, $value->month, $value->day)),

            $value instanceof \DateTimeInterface => $value,
            default => throw new \InvalidArgumentException(sprintf(
                'The date pipe expects a Date or a DateTimeInterface, got %s.',
                get_debug_type($value),
            )),
        };

        $formatter = new \IntlDateFormatter(
            $locale,
            DateStyle::fromName($style),
            \IntlDateFormatter::NONE,
            $moment->getTimezone(),
        );

        return $formatter->format($moment);
    }
}
