<?php

declare(strict_types=1);

namespace Medas\Templates\Pipes;

use Medas\Core\Attributes\Service;
use Medas\Templates\VariablePipe;

// Formats an integer as plain digits: 2923249 stays "2923249" in every locale. It is
// for text a program reads, such as a CSV, where the number pipe's locale grouping
// ("2.923.249") would be misread. Like the number pipe, it refuses a float.
#[Service]
readonly class IntegerPipe implements VariablePipe
{
    public function name(): string
    {
        return 'integer';
    }

    public function apply(mixed $value, string $locale): string
    {
        if (!is_int($value)) {
            throw new \InvalidArgumentException(sprintf(
                'The integer pipe expects an integer, got %s.',
                get_debug_type($value),
            ));
        }

        return (string) $value;
    }
}
