<?php

declare(strict_types=1);

namespace Medas\Templates;

// A named template pipe, used as {{ key | name:arg1:arg2 }}. The pipe also declares
// an apply() method - found by reflection rather than this interface, so each pipe can
// take its own typed, defaulted positional parameters after the value and the locale:
//
//   public function apply(mixed $value, string $locale, int $decimals = 2): string
//
// The PipeManager binds a token's colon-separated arguments to those parameters,
// coercing each to the parameter's declared type and falling back to its default when
// absent. apply() must return a string. Register a pipe by implementing this interface
// on a #[Service].
interface VariablePipe
{
    public function name(): string;
}
