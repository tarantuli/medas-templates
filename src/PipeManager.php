<?php

declare(strict_types=1);

namespace Medas\Templates;

use Medas\Core\{Attributes\Service, CachedImplementorList};

// Finds and invokes template pipes by name. A pipe's apply() is called by reflection,
// so its declared positional parameters - those after the value and locale - receive
// the token's colon-separated arguments, each coerced to the parameter's type, with
// the parameter's default used when an argument is absent. A variadic last parameter
// receives all the remaining arguments. This reflective dispatch is
// why VariablePipe declares only name(): every pipe has its own apply() signature.
#[Service]
readonly class PipeManager
{
    private CachedImplementorList $pipes;

    public function __construct()
    {
        $this->pipes = new CachedImplementorList(VariablePipe::class);
    }

    /**
     * Every pipe with the arguments its apply() takes after the value and the locale,
     * for an author who writes a template. Read from the same signatures apply() binds
     * to, so the description cannot drift from what a pipe accepts.
     *
     * @return Descriptions\PipeDescription[]
     */
    public function describe(): array
    {
        $descriptions = [];

        foreach ($this->pipes->get() as $pipe) {
            /** @var VariablePipe $pipe */
            $parameters = array_slice(new \ReflectionMethod($pipe, 'apply')->getParameters(), 2);

            $descriptions[] = new Descriptions\PipeDescription(
                $pipe->name(),
                array_map($this->describeArgument(...), $parameters),
            );
        }

        usort($descriptions, fn($a, $b) => $a->name <=> $b->name);

        return $descriptions;
    }

    private function describeArgument(\ReflectionParameter $parameter): Descriptions\ArgumentDescription
    {
        $type = $parameter->getType();
        $hasDefault = $parameter->isDefaultValueAvailable();

        return new Descriptions\ArgumentDescription(
            $parameter->name,
            $type instanceof \ReflectionNamedType ? $type->getName() : 'string',
            !$hasDefault && !$parameter->isVariadic(),
            $parameter->isVariadic(),
            $hasDefault ? $parameter->getDefaultValue() : null,
        );
    }

    /** @param string[] $arguments */
    public function apply(string $name, mixed $value, string $locale, array $arguments): string
    {
        foreach ($this->pipes->get() as $pipe) {
            /** @var VariablePipe $pipe */
            if ($pipe->name() === $name) {
                return $this->invoke($pipe, $value, $locale, $arguments);
            }
        }

        throw new \RuntimeException(sprintf('Unknown template pipe "%s".', $name));
    }

    /** @param string[] $arguments */
    private function invoke(VariablePipe $pipe, mixed $value, string $locale, array $arguments): string
    {
        $method = new \ReflectionMethod($pipe, 'apply');

        // apply()'s first two parameters are always the value and locale; the rest bind
        // to the token's arguments positionally.
        $parameters = array_slice($method->getParameters(), 2);
        $bound = [$value, $locale];

        foreach ($parameters as $index => $parameter) {
            if ($parameter->isVariadic()) {
                foreach (array_slice($arguments, $index) as $argument) {
                    $bound[] = $this->coerce($argument, $parameter);
                }

                break;
            }

            if (array_key_exists($index, $arguments)) {
                $bound[] = $this->coerce($arguments[$index], $parameter);
            }
            elseif ($parameter->isDefaultValueAvailable()) {
                $bound[] = $parameter->getDefaultValue();
            }
            else {
                throw new \RuntimeException(sprintf(
                    'Template pipe "%s" requires argument "%s".',
                    $pipe->name(),
                    $parameter->name,
                ));
            }
        }

        return (string) $method->invoke($pipe, ...$bound);
    }

    private function coerce(string $argument, \ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if (!$type instanceof \ReflectionNamedType) {
            return $argument;
        }

        return match ($type->getName()) {
            'int' => (int) $argument,
            'float' => (float) $argument,
            'bool' => filter_var($argument, FILTER_VALIDATE_BOOLEAN),
            default => $argument,
        };
    }
}
