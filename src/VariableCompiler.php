<?php

declare(strict_types=1);

namespace Medas\Templates;

use Medas\Core\{Attributes\Service, Interfaces\HasId, Types\Collection, Types\Relation};
use Medas\EntityManager\{MetaData, MetaDataManager};

// Compiles an entity into the raw, locale-free variable map templates draw on. Keys
// are "<prefix>:<property>": the subject's own #[IsTemplateVariable] properties under
// its (lower-cased) short name, and, for each marked reference on the subject, that
// entity's marked properties under the reference's name. Values stay in their native
// type - an int of cents, a Date, an enum, a string - so formatting and localisation
// belong to the resolver and its pipes, never here. A null value is kept as null so
// the resolver can tell "no value" from an unknown key.
//
// An entity with nothing marked compiles to an empty map.
#[Service]
readonly class VariableCompiler
{
    public function __construct(
        private MetaDataManager $metaDataManager,
    )
    {
    }

    /** @return array<string, mixed> */
    public function compile(HasId $entity): array
    {
        $variables = [];

        $this->addVariables(
            $variables,
            $this->rootPrefix($entity),
            $entity,
            followReferences: true
        );

        return $variables;
    }

    // Adds an entity's #[IsTemplateVariable] properties under "<prefix>:name". Marked
    // references are followed from the subject only: one hop, so a reference on a
    // walked entity is not followed on, which keeps the walk bounded and cycle-free.
    private function addVariables(array &$variables, string $prefix, HasId $entity, bool $followReferences): void
    {
        $metaData = $this->metaDataManager->get($entity::class);

        $this->refuseMarkedBackReferences($metaData);

        foreach ($metaData->properties as $property) {
            if ($property->attribute(Attributes\IsTemplateVariable::class) === null) {
                continue;
            }

            if ($property->type instanceof Collection) {
                throw new Exceptions\CollectionCannotBeATemplateVariable(
                    $metaData->className,
                    $property->name
                );
            }

            if (!$property->reflection->isInitialized($entity)) {
                continue;
            }

            $value = $property->reflection->getValue($entity);

            if ($property->type instanceof Relation) {
                if ($followReferences && $value instanceof HasId) {
                    $this->addVariables(
                        $variables,
                        $property->name,
                        $value,
                        followReferences: false
                    );
                }

                continue;
            }

            $variables[$prefix . ':' . $property->name] = $value;
        }
    }

    // A back-reference collection is not among the metadata's properties, so a marked
    // one would otherwise be ignored without a word. It has no single entity to take
    // values from.
    private function refuseMarkedBackReferences(MetaData $metaData): void
    {
        foreach ($metaData->backReferences as $backReference) {
            $reflection = new \ReflectionProperty($metaData->className, $backReference->name);

            if ($reflection->getAttributes(Attributes\IsTemplateVariable::class) !== []) {
                throw new Exceptions\CollectionCannotBeATemplateVariable(
                    $metaData->className,
                    $backReference->name
                );
            }
        }
    }

    private function rootPrefix(object $entity): string
    {
        return lcfirst(new \ReflectionClass($entity)->getShortName());
    }
}
