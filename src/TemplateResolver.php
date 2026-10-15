<?php

declare(strict_types=1);

namespace Medas\Templates;

use Medas\Core\Attributes\Service;

// Renders a template string against a compiled variable map. Each {{ key | pipe:arg |
// ... }} token resolves its key to a raw value (or, failing that, a declared default),
// then applies its pipes left to right. A value used without a pipe must already be a
// string: anything else - a cents int, a Date, an enum - has no safe un-piped
// rendering, so it throws rather than let a customer receive a raw "4999" or
// "2026-03-31". An unknown key throws too; a known-but-null value with no default
// renders empty.
//
// Every token's output is escaped for the document it lands in (see Escaping),
// unless its last pipe is "raw".
#[Service]
readonly class TemplateResolver
{
    private const string TOKEN_PATTERN = '/\{\{\s*(.+?)\s*}}/';

    // Not a pipe: it changes how the resolver treats the output, so it has no
    // apply() of its own.
    private const string RAW = 'raw';

    public function __construct(
        private PipeManager $pipeManager,
    )
    {
    }

    /**
     * @param array<string, mixed>  $values   Compiled variables in their native type.
     * @param array<string, string> $defaults Declared template-variable defaults.
     */
    public function render(
        string   $template,
        array    $values,
        array    $defaults,
        string   $locale,
        Escaping $escaping,
    ): string
    {
        return preg_replace_callback(
            self::TOKEN_PATTERN,
            fn(array $match) => $this->renderToken($match[1], $values, $defaults, $locale, $escaping),
            $template,
        );
    }

    /**
     * @param array<string, mixed>  $values
     * @param array<string, string> $defaults
     */
    private function renderToken(
        string   $token,
        array    $values,
        array    $defaults,
        string   $locale,
        Escaping $escaping,
    ): string
    {
        $segments = array_map(trim(...), explode('|', $token));
        $isRaw = count($segments) > 1 && end($segments) === self::RAW;

        if ($isRaw) {
            array_pop($segments);
        }

        $output = $this->resolveToken($segments, $values, $defaults, $locale);

        if ($isRaw || $escaping === Escaping::None) {
            return $output;
        }

        return htmlspecialchars($output, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    /**
     * @param string[]              $segments The key, then the pipes.
     * @param array<string, mixed>  $values
     * @param array<string, string> $defaults
     */
    private function resolveToken(array $segments, array $values, array $defaults, string $locale): string
    {
        $key = array_shift($segments);
        $value = $this->rawValue($key, $values, $defaults);

        if ($segments === []) {
            if (is_string($value)) {
                return $value;
            }

            throw new \RuntimeException(sprintf(
                'Template variable "%s" is not a string and needs a pipe (e.g. {{ %s | currency }}).',
                $key,
                $key,
            ));
        }

        foreach ($segments as $segment) {
            $value = $this->applyPipe($segment, $value, $locale);
        }

        return (string) $value;
    }

    /**
     * @param array<string, mixed>  $values
     * @param array<string, string> $defaults
     */
    private function rawValue(string $key, array $values, array $defaults): mixed
    {
        // Compiled data wins over a declared default; a default only fills a gap.
        if (array_key_exists($key, $values) && $values[$key] !== null) {
            return $values[$key];
        }

        if (array_key_exists($key, $defaults)) {
            return $defaults[$key];
        }

        if (array_key_exists($key, $values)) {
            // Known variable, but null and undefaulted - render empty rather than throw.
            return '';
        }

        throw new \RuntimeException(sprintf('Unknown template variable "%s".', $key));
    }

    private function applyPipe(string $segment, mixed $value, string $locale): string
    {
        $parts = array_map(trim(...), explode(':', $segment));
        $name = array_shift($parts);

        return $this->pipeManager->apply($name, $value, $locale, $parts);
    }
}
