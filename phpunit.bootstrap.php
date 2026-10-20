<?php

declare(strict_types=1);

use Medas\ObjectInstantiator\ObjectInstantiator;
use Medas\ServiceManager\{ServiceConfigBuilder, ServiceManager};
use Medas\Templates\TemplatesPackage;

chdir(__DIR__);

new ServiceManager(function (): ServiceConfigBuilder {
    $config = new ServiceConfigBuilder(ObjectInstantiator::class, isDev: true);

    $config->addPackages([
        TemplatesPackage::instance(),
    ]);

    return $config;
});
