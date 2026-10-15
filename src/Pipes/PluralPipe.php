<?php

declare(strict_types=1);

namespace Medas\Templates\Pipes;

use Medas\Core\Attributes\Service;
use Medas\Templates\VariablePipe;

// Chooses the form of a text that fits a count, by the locale's plural rules (ICU's,
// as Angular's i18n syntax uses): {{ months | plural:one=# maand:other=# maanden }}
// gives "1 maand" and "12 maanden". Each argument is <category>=<text>, with the
// categories zero, one, two, few, many and other, or an exact number such as 0=geen.
// "other" is required. In the text, # is the count, formatted for the locale.
//
// Dutch and English only use one and other; the other categories are there for
// languages that have them.
#[Service]
readonly class PluralPipe implements VariablePipe
{
    private const array CATEGORIES = ['zero', 'one', 'two', 'few', 'many', 'other'];

    public function name(): string
    {
        return 'plural';
    }

    public function apply(mixed $value, string $locale, string ...$forms): string
    {
        if (!is_int($value)) {
            throw new \InvalidArgumentException(sprintf(
                'The plural pipe expects an integer count, got %s.',
                get_debug_type($value),
            ));
        }

        $formatted = \MessageFormatter::formatMessage(
            $locale,
            '{count, plural, ' . $this->pattern($forms) . '}',
            ['count' => $value],
        );

        if ($formatted === false) {
            throw new \InvalidArgumentException(sprintf(
                'The plural pipe could not format its forms: %s.',
                intl_get_error_message(),
            ));
        }

        return $formatted;
    }

    /** @param string[] $forms */
    private function pattern(array $forms): string
    {
        $selectors = [];

        foreach ($forms as $form) {
            [$selector, $text] = array_pad(explode('=', $form, 2), 2, null);
            $selector = trim($selector);

            if ($text === null || !(in_array($selector, self::CATEGORIES, true) || ctype_digit($selector))) {
                throw new \InvalidArgumentException(sprintf(
                    'Plural form "%s" must be <category>=<text>, with a category of %s or an exact number.',
                    $form,
                    implode(', ', self::CATEGORIES),
                ));
            }

            $key = ctype_digit($selector) ? '=' . $selector : $selector;

            // An apostrophe quotes in ICU's syntax; doubled, it is a plain apostrophe.
            $selectors[$key] = $key . ' {' . str_replace("'", "''", $text) . '}';
        }

        if (!isset($selectors['other'])) {
            throw new \InvalidArgumentException('The plural pipe needs an "other" form.');
        }

        return implode(' ', $selectors);
    }
}
