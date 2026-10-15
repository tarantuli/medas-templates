<?php

declare(strict_types=1);

namespace Medas\Templates\Descriptions;

// A pipe and the arguments it takes, in order, as a template author writes them:
// {{ key | name:argument:argument }}.
final readonly class PipeDescription
{
    /** @param ArgumentDescription[] $arguments */
    public function __construct(
        public string $name,
        public array  $arguments,
    )
    {
    }
}
