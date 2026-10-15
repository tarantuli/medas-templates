<?php

declare(strict_types=1);

namespace Medas\TemplatesTest\Functional;

use Medas\Templates\{Escaping, TemplateResolver};
use PHPUnit\Framework\TestCase;

// The resolver's rules on fixed inputs: a string renders un-piped, anything else
// without a pipe throws, an unknown key throws, a declared default fills a gap, and a
// compiled value beats a default.
class TemplateResolverTest extends TestCase
{
    public function testAStringRendersWithoutAPipe(): void
    {
        self::assertSame('hello', $this->resolver()->render('{{ x }}', ['x' => 'hello'], [], 'nl-NL', Escaping::None));
    }

    public function testTextAroundTokensIsKept(): void
    {
        self::assertSame(
            'Dear Ann, welcome.',
            $this->resolver()->render('Dear {{name}}, {{ greeting }}.', ['name' => 'Ann', 'greeting' => 'welcome'], [], 'nl-NL', Escaping::None)
        );
    }

    public function testADefaultFillsAGap(): void
    {
        self::assertSame('fallback', $this->resolver()->render('{{ x }}', [], ['x' => 'fallback'], 'nl-NL', Escaping::None));
    }

    public function testAValueBeatsADefault(): void
    {
        self::assertSame(
            'real',
            $this->resolver()->render('{{ x }}', ['x' => 'real'], ['x' => 'fallback'], 'nl-NL', Escaping::None)
        );
    }

    public function testANonStringWithoutAPipeIsRefused(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->resolver()->render('{{ x }}', ['x' => 4999], [], 'nl-NL', Escaping::None);
    }

    public function testAnUnknownKeyIsRefused(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->resolver()->render('{{ x }}', [], [], 'nl-NL', Escaping::None);
    }

    public function testAnUnknownPipeIsRefused(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->resolver()->render('{{ x | nope }}', ['x' => 'y'], [], 'nl-NL', Escaping::None);
    }

    // In HTML every token's output is escaped, so a value cannot add markup.
    public function testHtmlEscapesEveryToken(): void
    {
        self::assertSame(
            '<p>Smith &amp; &lt;Sons&gt; &quot;Ltd&quot; &apos;s</p>',
            $this->resolver()->render('<p>{{ name }}</p>', ['name' => 'Smith & <Sons> "Ltd" \'s'], [], 'nl-NL', Escaping::Html)
        );
    }

    // The escaping is applied after the pipes, to what they produce.
    public function testHtmlEscapesThePipedOutput(): void
    {
        self::assertSame(
            'A &amp; B',
            $this->resolver()->render('{{ flag | yesno:A & B:C }}', ['flag' => true], [], 'nl-NL', Escaping::Html)
        );
    }

    public function testRawLeavesATokenUnescaped(): void
    {
        self::assertSame(
            '<b>bold</b>',
            $this->resolver()->render('{{ html | raw }}', ['html' => '<b>bold</b>'], [], 'nl-NL', Escaping::Html)
        );
    }

    // Raw only switches escaping off; the rule that a value needs a pipe still holds.
    public function testRawDoesNotMakeANonStringRenderable(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->resolver()->render('{{ x | raw }}', ['x' => 4999], [], 'nl-NL', Escaping::Html);
    }

    public function testPlainTextIsNotEscaped(): void
    {
        self::assertSame(
            'Smith & <Sons>',
            $this->resolver()->render('{{ name }}', ['name' => 'Smith & <Sons>'], [], 'nl-NL', Escaping::None)
        );
    }

    private function resolver(): TemplateResolver
    {
        return service(TemplateResolver::class);
    }
}
