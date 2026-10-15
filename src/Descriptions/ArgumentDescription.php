<?php

declare(strict_types=1);

namespace Medas\Templates\Descriptions;

// One positional argument of a pipe. A variadic argument takes all the remaining
// ones; an optional one has the default it falls back to.
final readonly class ArgumentDescription
{
    public function __construct(
        public string                     $name,
        public string                     $type,
        public bool                       $isRequired,
        public bool                       $isVariadic,
        public string|int|float|bool|null $default,
    )
    {
    }
}
