<?php

declare(strict_types=1);

namespace Medas\Templates\Pipes;

use Medas\Core\Attributes\Service;
use Medas\Templates\VariablePipe;

// Formats an integer amount of minor units (cents) as localised currency: 4999 becomes
// "€ 49,99" in nl-NL, "€49.99" in en-GB. An int does not say that it holds an amount
// in cents, so the template author supplies that knowledge by naming this pipe:
// {{ invoice:total | currency }}. The currency defaults to euro and can be overridden
// positionally: {{ price | currency:USD }}.
#[Service]
readonly class CurrencyPipe implements VariablePipe
{
    private const string DEFAULT_CURRENCY = 'EUR';

    public function name(): string
    {
        return 'currency';
    }

    public function apply(mixed $value, string $locale, string $currency = self::DEFAULT_CURRENCY): string
    {
        if (!is_int($value)) {
            throw new \InvalidArgumentException(sprintf(
                'The currency pipe expects an integer amount in minor units, got %s.',
                get_debug_type($value),
            ));
        }

        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($value / 100, $currency);
    }
}
