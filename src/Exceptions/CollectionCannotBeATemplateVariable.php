<?php

declare(strict_types=1);

namespace Medas\Templates\Exceptions;

use Medas\Core\Exceptions\BaseException;

// A collection has no single entity whose properties a template could use.
class CollectionCannotBeATemplateVariable extends BaseException
{
    public function __construct(string $className, string $propertyName)
    {
        parent::__construct($className, $propertyName);
    }

    public function pattern(): string
    {
        return '%s::$%s is a collection and cannot be marked #[IsTemplateVariable]';
    }
}
