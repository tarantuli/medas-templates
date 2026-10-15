<?php

declare(strict_types=1);

namespace Medas\Templates\Pipes;

use Medas\Core\Attributes\Service;
use Medas\Templates\VariablePipe;

// Formats a whole-number percentage - 21 for 21% - with the locale's percent sign and
// spacing: "21%" in en-GB, "21%" in nl-NL. The value is the percentage itself, not a
// fraction, as percentages are stored. Decimals default to none and can be set
// positionally: {{ rate | percent:1 }} gives "9,5%" for 9.5.
#[Service]
readonly class PercentPipe implements VariablePipe
{
    public function name(): string
    {
        return 'percent';
    }

    public function apply(mixed $value, string $locale, int $decimals = 0): string
    {
        if (!is_int($value) && !is_float($value)) {
            throw new \InvalidArgumentException(sprintf(
                'The percent pipe expects a number, got %s.',
                get_debug_type($value),
            ));
        }

        $formatter = new \NumberFormatter($locale, \NumberFormatter::PERCENT);

        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $decimals);

        return $formatter->format($value / 100);
    }
}
