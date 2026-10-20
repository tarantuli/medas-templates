<?php

declare(strict_types=1);

namespace Medas\Templates\Interfaces;

// Supplies an enum case's label from outside the enum, for an application that keeps
// its labels as data rather than in code. When an implementation is registered, the
// enum pipe asks it first; null means it has no label for that enum, and the pipe
// falls back to HasLabel.
interface LabelProvider
{
    public function label(\UnitEnum $case, string $locale): string|null;
}
