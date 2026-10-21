<?php

declare(strict_types=1);

namespace Medas\Templates\Pipes;

use Medas\Core\{Attributes\Service, Date};
use Medas\Templates\VariablePipe;

// Formats a date with an explicit ICU pattern: {{ invoice:date | dateformat }} gives
// "2026-03-31", {{ invoice:date | dateformat:dd-MM-yyyy }} "31-03-2026". It is for text
// a program reads, such as a CSV, which needs one fixed format rather than the date
// pipe's localised styles. Month and day names in a pattern (MMM, EEEE) follow the
// locale. A pattern cannot contain ":" or "|", which separate arguments and pipes, so
// a time of day is out of reach here.
#[Service]
readonly class DateFormatPipe implements VariablePipe
{
    public function name(): string
    {
        return 'dateformat';
    }

    public function apply(mixed $value, string $locale, string $pattern = 'yyyy-MM-dd'): string
    {
        $moment = match (true) {
            $value instanceof Date
                => new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $value->year, $value->month, $value->day)),

            $value instanceof \DateTimeInterface => $value,
            default => throw new \InvalidArgumentException(sprintf(
                'The dateformat pipe expects a Date or a DateTimeInterface, got %s.',
                get_debug_type($value),
            )),
        };

        $formatter = new \IntlDateFormatter(
            $locale,
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            $moment->getTimezone(),
            null,
            $pattern,
        );

        return $formatter->format($moment);
    }
}
