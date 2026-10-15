<?php

declare(strict_types=1);

namespace Medas\Templates\Pipes;

use Medas\Core\Attributes\Service;
use Medas\Templates\VariablePipe;

// Renders a bool as one of two words the author gives: {{ autoRenew | yesno:Ja:Nee }}.
// Both are required: template content is written per locale, so the template already
// knows its language, and a built-in default would put English words in a Dutch text.
#[Service]
readonly class YesNoPipe implements VariablePipe
{
    public function name(): string
    {
        return 'yesno';
    }

    public function apply(mixed $value, string $locale, string $yes, string $no): string
    {
        if (!is_bool($value)) {
            throw new \InvalidArgumentException(sprintf(
                'The yesno pipe expects a bool, got %s.',
                get_debug_type($value),
            ));
        }

        return $value ? $yes : $no;
    }
}
