<?php

declare(strict_types=1);

namespace Medas\Templates;

use Medas\Core\{AsSingleton, BasePackage};

class TemplatesPackage extends BasePackage
{
    use AsSingleton;

    public function dependencies(): array
    {
        return [];
    }

    public function sourceDirectory(): string
    {
        return __DIR__;
    }
}
