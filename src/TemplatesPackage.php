<?php

declare(strict_types=1);

namespace Medas\Templates;

use Medas\Core\{AsSingleton, BasePackage};
use Medas\EntityManager\EntityManagerPackage;
use Medas\ObjectInstantiator\ObjectInstantiatorPackage;

class TemplatesPackage extends BasePackage
{
    use AsSingleton;

    // The services take other services by constructor injection, which the object
    // instantiator's parameter resolvers provide; VariableCompiler reads entity metadata.
    public function dependencies(): array
    {
        return [
            EntityManagerPackage::instance(),
            ObjectInstantiatorPackage::instance(),
        ];
    }

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
