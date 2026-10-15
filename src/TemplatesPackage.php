<?php

declare(strict_types=1);

namespace Medas\Templates;

use Medas\Core\{AsSingleton, BasePackage};
use Medas\EntityManager\EntityManagerPackage;
use Medas\ObjectInstantiator\ObjectInstantiatorPackage;

class TemplatesPackage extends BasePackage
{
    use AsSingleton;

    // VariableCompiler reads entity metadata.
    public function dependencies(): array
    {
        return [
            EntityManagerPackage::instance(),
        ];
    }

    // The tests build the services themselves, so they need the object instantiator's
    // parameter resolvers for constructor injection; an application registers those
    // already.
    public function devDependencies(): array
    {
        return [
            ObjectInstantiatorPackage::instance(),
        ];
    }

    public function sourceDirectory(): string
    {
        return __DIR__;
    }
}
