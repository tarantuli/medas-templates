<?php

declare(strict_types=1);

namespace Medas\Templates\Pipes;

use Medas\Core\Attributes\Service;
use Medas\Templates\VariablePipe;

// Formats an integer amount of minor units (cents) as a plain decimal number: 4999
// becomes "49.99" and -1250 "-12.50". It is for text a program reads, such as a CSV
// for an accounting system, so it has no currency sign and no grouping whatever the
// locale; the currency pipe is the one for a person. The decimal separator defaults to
// a point and can be set positionally: {{ charge:amount | money:, }}.
#[Service]
readonly class MoneyPipe implements VariablePipe
{
    public function name(): string
    {
        return 'money';
    }

    public function apply(mixed $value, string $locale, string $decimalSeparator = '.'): string
    {
        if (!is_int($value)) {
            throw new \InvalidArgumentException(sprintf(
                'The money pipe expects an integer amount in minor units, got %s.',
                get_debug_type($value),
            ));
        }

        $sign = $value < 0 ? '-' : '';
        $minorUnits = abs($value);

        return sprintf(
            '%s%d%s%02d',
            $sign,
            intdiv($minorUnits, 100),
            $decimalSeparator,
            $minorUnits % 100
        );
    }
}
