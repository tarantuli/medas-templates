<?php

declare(strict_types=1);

namespace Medas\Templates\Descriptions;

// One key a template can use, as VariableCompiler would produce it. The type tells a
// template author which pipe the value needs: a string renders as it is, an int of
// cents needs currency, a Date needs date. Nullable means the value can be absent, so
// the token can render empty.
final readonly class VariableDescription
{
    public function __construct(
        public string $key,
        public string $type,
        public bool   $isNullable,
    )
    {
    }
}
