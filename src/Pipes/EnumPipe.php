<?php

declare(strict_types=1);

namespace Medas\Templates\Pipes;

use Medas\Core\Attributes\Service;
use Medas\Templates\{Interfaces\HasLabel, Interfaces\LabelProvider, VariablePipe};

// Renders an enum case as its label in the reader's locale: {{ subscription:state |
// enum }}. The label comes from a registered LabelProvider, else from the enum itself
// through HasLabel. A case name or backing value is not text for a reader, so an enum
// with neither is refused.
#[Service]
readonly class EnumPipe implements VariablePipe
{
    public function __construct(
        private LabelProvider|null $labelProvider = null,
    )
    {
    }

    public function name(): string
    {
        return 'enum';
    }

    public function apply(mixed $value, string $locale): string
    {
        if (!$value instanceof \UnitEnum) {
            throw new \InvalidArgumentException(sprintf(
                'The enum pipe expects an enum, got %s.',
                get_debug_type($value),
            ));
        }

        $label = $this->labelProvider?->label($value, $locale);

        if ($label !== null) {
            return $label;
        }

        if ($value instanceof HasLabel) {
            return $value->label($locale);
        }

        throw new \InvalidArgumentException(sprintf(
            'The enum pipe has no label for %s: register a %s that knows it, or implement %s.',
            $value::class,
            LabelProvider::class,
            HasLabel::class,
        ));
    }
}
