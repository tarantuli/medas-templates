<?php

declare(strict_types=1);

namespace Medas\TemplatesTest\Functional;

use Medas\Core\Date;
use Medas\Templates\{Escaping, Interfaces\LabelProvider, Pipes\EnumPipe, TemplateResolver};
use Medas\TemplatesTest\MockUps\{Colour, Unlabelled};
use PHPUnit\Framework\TestCase;

// The pipes localise: grouping and decimal marks follow the locale, an argument
// overrides a default positionally, and a pipe refuses a value it cannot format. The
// expectations are matched loosely where ICU's symbols and spacing differ between
// versions.
class PipesTest extends TestCase
{
    public function testNumberGroupsPerLocale(): void
    {
        self::assertStringContainsString(
            '2.923.249',
            $this->render('{{ n | number }}', ['n' => 2923249], 'nl-NL')
        );

        self::assertStringContainsString(
            '2,923,249',
            $this->render('{{ n | number }}', ['n' => 2923249], 'en-GB')
        );
    }

    public function testNumberRefusesAFloat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->render('{{ f | number }}', ['f' => 1.5], 'nl-NL');
    }

    public function testCurrencyFormatsCentsPerLocale(): void
    {
        self::assertStringContainsString(
            '49,99',
            $this->render('{{ p | currency }}', ['p' => 4999], 'nl-NL')
        );
    }

    public function testCurrencyTakesTheCurrencyAsAnArgument(): void
    {
        $rendered = $this->render('{{ p | currency:USD }}', ['p' => 4999], 'en-GB');

        self::assertStringContainsString('49.99', $rendered);
        self::assertStringNotContainsString('€', $rendered);
    }

    public function testCurrencyRefusesANonInteger(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->render('{{ p | currency }}', ['p' => '49.99'], 'nl-NL');
    }

    public function testDateFormatsPerLocaleAndStyle(): void
    {
        $date = new Date(2026, 3, 31);

        self::assertStringContainsString(
            'maart',
            $this->render('{{ d | date:long }}', ['d' => $date], 'nl-NL')
        );

        self::assertStringContainsString(
            'March',
            $this->render('{{ d | date:long }}', ['d' => $date], 'en-GB')
        );

        self::assertStringContainsString(
            '2026',
            $this->render('{{ d | date }}', ['d' => $date], 'nl-NL')
        );
    }

    // A moment gives its date part, in its own time zone: 23:30 in Amsterdam on the
    // 31st is still the 31st, although it is the 1st in some later zone.
    public function testDateTakesTheDatePartOfAMoment(): void
    {
        $moment = new \DateTimeImmutable('2026-03-31 23:30', new \DateTimeZone('Europe/Amsterdam'));

        self::assertStringContainsString(
            '31 maart 2026',
            $this->render('{{ d | date:long }}', ['d' => $moment], 'nl-NL')
        );
    }

    public function testDateRefusesAnUnknownStyle(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->render('{{ d | date:huge }}', ['d' => new Date(2026, 3, 31)], 'nl-NL');
    }

    public function testDateRefusesANonDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->render('{{ d | date }}', ['d' => '2026-03-31'], 'nl-NL');
    }

    public function testDateTimeShowsTheTimeInTheValuesZone(): void
    {
        $moment = new \DateTimeImmutable('2026-03-31 14:05', new \DateTimeZone('Europe/Amsterdam'));
        $rendered = $this->render('{{ t | datetime }}', ['t' => $moment], 'nl-NL');

        self::assertStringContainsString('14:05', $rendered);
        self::assertStringContainsString('2026', $rendered);
    }

    public function testDateTimeTakesBothStyles(): void
    {
        $moment = new \DateTimeImmutable(
            '2026-03-31 14:05:09',
            new \DateTimeZone('Europe/Amsterdam')
        );

        $rendered = $this->render('{{ t | datetime:long:medium }}', ['t' => $moment], 'en-GB');

        self::assertStringContainsString('March', $rendered);
        self::assertStringContainsString('14:05:09', $rendered);
    }

    public function testDateTimeRefusesADate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->render('{{ t | datetime }}', ['t' => new Date(2026, 3, 31)], 'nl-NL');
    }

    public function testPercentTakesTheWholeNumber(): void
    {
        self::assertSame('21%', $this->render('{{ v | percent }}', ['v' => 21], 'en-GB'));

        self::assertStringContainsString(
            '21',
            $this->render('{{ v | percent }}', ['v' => 21], 'nl-NL')
        );
    }

    public function testPercentTakesDecimals(): void
    {
        self::assertStringContainsString(
            '9,5',
            $this->render('{{ v | percent:1 }}', ['v' => 9.5], 'nl-NL')
        );

        self::assertStringContainsString(
            '9.5',
            $this->render('{{ v | percent:1 }}', ['v' => 9.5], 'en-GB')
        );
    }

    public function testPercentRefusesANonNumber(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->render('{{ v | percent }}', ['v' => '21'], 'nl-NL');
    }

    public function testEnumRendersTheLabelForTheLocale(): void
    {
        self::assertSame('rood', $this->render('{{ c | enum }}', ['c' => Colour::Red], 'nl-NL'));
        self::assertSame('green', $this->render('{{ c | enum }}', ['c' => Colour::Green], 'en-GB'));
    }

    public function testEnumRefusesAnEnumWithoutLabels(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->render('{{ c | enum }}', ['c' => Unlabelled::Something], 'nl-NL');
    }

    // A registered provider is asked first; where it has nothing, HasLabel answers.
    public function testEnumAsksTheLabelProviderFirst(): void
    {
        $provider = new class implements LabelProvider
        {
            public function label(\UnitEnum $case, string $locale): string|null
            {
                return $case === Unlabelled::Something ? "iets ($locale)" : null;
            }
        };

        $pipe = new EnumPipe($provider);

        self::assertSame('iets (nl-NL)', $pipe->apply(Unlabelled::Something, 'nl-NL'));
        self::assertSame('rood', $pipe->apply(Colour::Red, 'nl-NL'));
    }

    public function testEnumRefusesANonEnum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->render('{{ c | enum }}', ['c' => 'Red'], 'nl-NL');
    }

    public function testYesNoUsesTheGivenWords(): void
    {
        self::assertSame('Ja', $this->render('{{ b | yesno:Ja:Nee }}', ['b' => true], 'nl-NL'));
        self::assertSame('Nee', $this->render('{{ b | yesno:Ja:Nee }}', ['b' => false], 'nl-NL'));
    }

    // Without the words the pipe would have to guess a language.
    public function testYesNoRequiresBothWords(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->render('{{ b | yesno:Ja }}', ['b' => true], 'nl-NL');
    }

    public function testYesNoRefusesANonBool(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->render('{{ b | yesno:Ja:Nee }}', ['b' => 1], 'nl-NL');
    }

    public function testPluralChoosesTheFormByTheLocalesRules(): void
    {
        $template = '{{ m | plural:one=# maand:other=# maanden }}';

        self::assertSame('1 maand', $this->render($template, ['m' => 1], 'nl-NL'));
        self::assertSame('12 maanden', $this->render($template, ['m' => 12], 'nl-NL'));
        self::assertSame('0 maanden', $this->render($template, ['m' => 0], 'nl-NL'));
    }

    public function testPluralFormatsTheCountForTheLocale(): void
    {
        self::assertSame(
            '1.500 dagen',
            $this->render('{{ d | plural:one=# dag:other=# dagen }}', ['d' => 1500], 'nl-NL')
        );
    }

    public function testPluralTakesAnExactNumber(): void
    {
        self::assertSame(
            'geen maanden',
            $this->render(
                '{{ m | plural:0=geen maanden:one=# maand:other=# maanden }}',
                ['m' => 0],
                'nl-NL'
            )
        );
    }

    // An apostrophe is a quote in ICU's syntax; in a form it is just an apostrophe.
    public function testPluralKeepsAnApostrophe(): void
    {
        self::assertSame(
            "3 user's seats",
            $this->render(
                "{{ n | plural:one=# user's seat:other=# user's seats }}",
                ['n' => 3],
                'en-GB'
            )
        );
    }

    public function testPluralRequiresAnOtherForm(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->render('{{ m | plural:one=# maand }}', ['m' => 1], 'nl-NL');
    }

    public function testPluralRefusesAnUnknownCategory(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->render('{{ m | plural:several=# maanden:other=# maanden }}', ['m' => 3], 'nl-NL');
    }

    public function testPluralRefusesANonInteger(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->render('{{ m | plural:one=# maand:other=# maanden }}', ['m' => '3'], 'nl-NL');
    }

    public function testMoneyGivesAPlainDecimalInEveryLocale(): void
    {
        self::assertSame('2923.49', $this->render('{{ p | money }}', ['p' => 292349], 'nl-NL'));
        self::assertSame('0.05', $this->render('{{ p | money }}', ['p' => 5], 'en-GB'));
        self::assertSame('-12.50', $this->render('{{ p | money }}', ['p' => -1250], 'nl-NL'));
    }

    public function testMoneyTakesTheDecimalSeparatorAsAnArgument(): void
    {
        self::assertSame('49,99', $this->render('{{ p | money:, }}', ['p' => 4999], 'en-GB'));
    }

    public function testMoneyRefusesANonInteger(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->render('{{ p | money }}', ['p' => 49.99], 'nl-NL');
    }

    public function testIntegerGivesPlainDigitsInEveryLocale(): void
    {
        self::assertSame('2923249', $this->render('{{ n | integer }}', ['n' => 2923249], 'nl-NL'));
        self::assertSame('-3', $this->render('{{ n | integer }}', ['n' => -3], 'en-GB'));
    }

    public function testIntegerRefusesAFloat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->render('{{ n | integer }}', ['n' => 1.5], 'nl-NL');
    }

    public function testDateFormatDefaultsToIsoDate(): void
    {
        self::assertSame(
            '2026-03-31',
            $this->render('{{ d | dateformat }}', ['d' => new Date(2026, 3, 31)], 'nl-NL')
        );
    }

    public function testDateFormatTakesAPattern(): void
    {
        self::assertSame(
            '31-03-2026',
            $this->render('{{ d | dateformat:dd-MM-yyyy }}', ['d' => new Date(2026, 3, 31)], 'en-GB')
        );
    }

    public function testDateFormatRefusesANonDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->render('{{ d | dateformat }}', ['d' => '2026-03-31'], 'nl-NL');
    }

    private function render(string $template, array $values, string $locale): string
    {
        return service(TemplateResolver::class)->render(
            $template,
            $values,
            [],
            $locale,
            Escaping::None
        );
    }
}
