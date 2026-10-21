<?php

declare(strict_types=1);

namespace Medas\Templates;

use Medas\Core\{Attributes\Service, Interfaces\HasId, Types\Collection, Types\Relation};
use Medas\EntityManager\{MetaData, MetaData\Property, MetaDataManager};

// Compiles an entity into the raw, locale-free variable map templates draw on. Keys
// are "<prefix>:<property>": the subject's own #[IsTemplateVariable] properties under
// its (lower-cased) short name, and, for each marked reference on the subject, that
// entity's marked properties under the reference's name. Values stay in their native
// type - an int of cents, a Date, an enum, a string - so formatting and localisation
// belong to the resolver and its pipes, never here. A null value is kept as null so
// the resolver can tell "no value" from an unknown key; that includes every key of a
// marked reference that is null, so a template written for the reference renders
// empty there instead of failing.
//
// describe() lists the same keys from the metadata alone, for an author writing a
// template. An entity with nothing marked compiles to an empty map.
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
            $this->rootPrefix($entity::class),
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
        foreach ($this->markedProperties($entity::class) as $property) {
            $isSet = $property->reflection->isInitialized($entity);
            $value = $isSet ? $property->reflection->getValue($entity) : null;

            if ($this->isReference($property)) {
                if (!$followReferences) {
                    continue;
                }

                if ($value instanceof HasId) {
                    $this->addVariables(
                        $variables,
                        $property->name,
                        $this->loaded($value),
                        followReferences: false
                    );
                }
                else {
                    $this->addNullVariables($variables, $property->name, $property->type->entity);
                }

                continue;
            }

            if ($isSet) {
                $variables[$prefix . ':' . $property->name] = $value;
            }
        }
    }

    // A reference is often a lazy object, whose properties read as uninitialised through
    // reflection until it is loaded. Loading it is a no-op for an object that is not lazy.
    private function loaded(HasId $entity): HasId
    {
        new \ReflectionClass($entity)->initializeLazyObject($entity);

        return $entity;
    }

    // The keys of a marked reference that is null: known, so they render empty.
    private function addNullVariables(array &$variables, string $prefix, string $className): void
    {
        foreach ($this->markedProperties($className) as $property) {
            if (!$this->isReference($property)) {
                $variables[$prefix . ':' . $property->name] = null;
            }
        }
    }

    /**
     * The keys compile() produces for an entity of this class, with their types.
     *
     * @param class-string $className
     *
     * @return Descriptions\VariableDescription[]
     */
    public function describe(string $className): array
    {
        $descriptions = [];

        $this->addDescriptions(
            $descriptions,
            $this->rootPrefix($className),
            $className,
            false,
            followReferences: true
        );

        return $descriptions;
    }

    private function addDescriptions(
        array  &$descriptions,
        string $prefix,
        string $className,
        bool   $viaNullableReference,
        bool   $followReferences,
    ): void
    {
        foreach ($this->markedProperties($className) as $property) {
            if ($this->isReference($property)) {
                if ($followReferences) {
                    $this->addDescriptions(
                        $descriptions,
                        $property->name,
                        $property->type->entity,
                        $property->isNullable,
                        followReferences: false,
                    );
                }

                continue;
            }

            $descriptions[] = new Descriptions\VariableDescription(
                $prefix . ':' . $property->name,
                $this->typeName($property),
                $viaNullableReference || $property->isNullable,
                $property->attribute(Attributes\IsTemplateVariable::class)->isMinorUnits,
            );
        }
    }

    /** @return Property[] */
    private function markedProperties(string $className): array
    {
        $metaData = $this->metaDataManager->get($className);

        $this->refuseMarkedBackReferences($metaData);

        $marked = [];

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

            $marked[] = $property;
        }

        return $marked;
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

    // Whether a property points at another entity, to be followed. The entity manager
    // types an enum property as a relation to the enum, but an enum case is a value,
    // rendered with the enum pipe.
    private function isReference(Property $property): bool
    {
        return $property->type instanceof Relation && !enum_exists($property->type->entity);
    }

    // The type as an author reads it: "int", "string", "Date", an enum's short name.
    private function typeName(Property $property): string
    {
        $names = array_values(array_filter($property->phpTypes, fn(string $name) => $name !== 'null'));
        $name = $names[0] ?? 'mixed';

        return str_contains($name, '\\') ? substr($name, strrpos($name, '\\') + 1) : $name;
    }

    private function rootPrefix(string $className): string
    {
        return lcfirst(new \ReflectionClass($className)->getShortName());
    }
}
