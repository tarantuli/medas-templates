<?php

declare(strict_types=1);

namespace Medas\Templates\Pipes;

use Medas\Core\Attributes\Service;
use Medas\Templates\{Interfaces\HasLabel, VariablePipe};

// Renders an enum case as its label in the reader's locale: {{ subscription:state |
// enum }}. The enum supplies the label by implementing HasLabel; a case name or a
// backing value is not text for a reader, so an enum without labels is refused.
#[Service]
readonly class EnumPipe implements VariablePipe
{
    public function name(): string
    {
        return 'enum';
    }

    public function apply(mixed $value, string $locale): string
    {
        if (!$value instanceof \UnitEnum || !$value instanceof HasLabel) {
            throw new \InvalidArgumentException(sprintf(
                'The enum pipe expects an enum that implements %s, got %s.',
                HasLabel::class,
                get_debug_type($value),
            ));
        }

        return $value->label($locale);
    }
}
