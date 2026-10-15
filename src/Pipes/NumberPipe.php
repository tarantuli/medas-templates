<?php

declare(strict_types=1);

namespace Medas\Templates\Pipes;

use Medas\Core\Attributes\Service;
use Medas\Templates\VariablePipe;

// Formats an integer with locale grouping: 2923249 becomes "2.923.249" in nl-NL and
// "2,923,249" in en-GB. Plain integer fields (quantities, counts) are common, and the
// strict rule keeps them out of templates without a pipe - this is the pipe for them.
// It deliberately rejects floats: a non-integer amount is either money (use currency)
// or wants its own rounding decision, not silent grouping.
#[Service]
readonly class NumberPipe implements VariablePipe
{
    public function name(): string
    {
        return 'number';
    }

    public function apply(mixed $value, string $locale): string
    {
        if (!is_int($value)) {
            throw new \InvalidArgumentException(sprintf(
                'The number pipe expects an integer, got %s.',
                get_debug_type($value),
            ));
        }

        $formatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);

        return $formatter->format($value);
    }
}
