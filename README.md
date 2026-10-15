# medas-templates

Part of the [Medas framework](https://github.com/tarantuli/medas-core).

## Description

Renders a template string with Angular-style interpolation: `{{ key }}` and
`{{ key | pipe:argument:argument }}`. It replaces `medas-html-templates` for new work.
Nothing in it is HTML-specific: it fills e-mail subjects, plain text and markup alike.

It works in two steps that know nothing of each other:

1. **Compile**: `VariableCompiler` turns an entity into a map of raw values, from the
   properties marked as template variables. No formatting, no locale.
2. **Render**: `TemplateResolver` fills a template from such a map, for a locale. No
   entity access.

The values are given in their native type (an int of cents, a `Date`, a string), and
formatting is the template's job, through pipes. The rules are strict on purpose:

- A value used without a pipe must already be a string. Anything else (an int, a
  `Date`, an enum) has no safe un-piped rendering, so it throws rather than let a
  reader see a raw `4999` or `2026-03-31`.
- An unknown key throws. A known key whose value is null renders empty or its
  declared default.
- An unknown pipe, or a pipe given a value it cannot format, throws.

**Services and interfaces:**

| Class                 | Purpose                                                                            |
|-----------------------|------------------------------------------------------------------------------------|
| `VariableCompiler`    | Compiles an entity into a map of `prefix:property` values, in their native type    |
| `TemplateResolver`    | Renders a template against a map of values and a map of defaults, for a locale     |
| `PipeManager`         | Finds a pipe by name and calls it, binding the token's arguments to its parameters |
| `VariablePipe`        | The interface a pipe implements; its `apply()` is found by reflection (see below)  |
| `Escaping`            | How a render call escapes its output: `None` or `Html`                             |
| `Interfaces\HasLabel` | Implemented by an enum (or any value) to give the `enum` pipe its label per locale |

**Pipes:**

| Pipe       | Takes                                | Example                                               | nl-NL                |
|------------|--------------------------------------|-------------------------------------------------------|----------------------|
| `number`   | an int                               | `{{ quantity \| number }}`                            | `2.923.249`          |
| `currency` | an int of minor units (cents)        | `{{ total \| currency }}`, `currency:USD`             | `€ 49,99`            |
| `percent`  | a whole-number percentage (21 = 21%) | `{{ vat \| percent }}`, `percent:1`                   | `21%`                |
| `date`     | a `Medas\Core\Date` or a moment      | `{{ start \| date:long }}`                            | `31 maart 2026`      |
| `datetime` | a `\DateTimeInterface`               | `{{ createdAt \| datetime }}`, `datetime:long:medium` | `31 mrt 2026, 14:05` |
| `enum`     | an enum implementing `HasLabel`      | `{{ state \| enum }}`                                 | its label            |
| `yesno`    | a bool                               | `{{ autoRenew \| yesno:Ja:Nee }}`                     | `Ja`                 |
| `plural`   | an int count                         | `{{ months \| plural:one=# maand:other=# maanden }}`  | `12 maanden`         |

- `date` and `datetime` take the styles `short`, `medium`, `long` and `full`; an
  unknown style throws. `date` defaults to `medium`; `datetime` to `medium` for the
  date and `short` for the time. A moment is shown in its own time zone.
- `currency` defaults to `EUR`. `percent` defaults to no decimals.
- `enum` calls `label($locale)` on the enum (`Medas\Templates\Interfaces\HasLabel`).
  A case name or backing value is not text for a reader, so an enum without labels
  is refused.
- `yesno` requires both words: content is written per locale, so the template knows
  its language, and a default would put English in a Dutch text.
- `plural` follows the locale's plural rules (ICU's, like Angular's i18n syntax).
  Each argument is `<category>=<text>`, with the categories `zero`, `one`, `two`,
  `few`, `many` and `other`, or an exact number (`0=geen maanden`). `other` is
  required. `#` in a text is the count, formatted for the locale.

An argument cannot contain `:` or `|`, which separate arguments and pipes.

**Escaping:**

`render()` takes how its output is escaped, per call: `Escaping::Html` for an HTML
document (every token's output has `& < > " '` turned into entities), `Escaping::None`
for plain text such as an e-mail subject. It is a required argument, so a caller
cannot forget it. A token ending in `| raw` is left unescaped, for a value known to be
safe markup; it does not lift the rule that a non-string needs a pipe.

## Usage

### Package developer context

Register the package:

```php
use Medas\Templates\TemplatesPackage;

TemplatesPackage::instance();
```

**Exposing an entity to templates:**

Exposure is opt-in per property: being readable through the API does not make a
property available in a template.

```php
use Medas\Templates\Attributes\IsTemplateVariable;

#[Entity(store: 'subscriptions')]
class Subscription implements HasId
{
    #[IsTemplateVariable]
    public string $subscriptionNumber;

    #[IsTemplateVariable]
    public int $price;

    #[IsTemplateVariable]
    public Tenant $tenant;

    #[IsTemplateVariable]
    public Product $product;
}
```

`service(VariableCompiler::class)->compile($subscription)` then returns, for example:

```php
[
    'subscription:subscriptionNumber' => 'S-2026-0007',
    'subscription:price' => 4999,
    'tenant:name' => 'Contoso B.V.',
    'product:name' => 'Proxmox Premium',
]
```

- The subject's own properties sit under its short class name, lower-cased first.
- A marked to-one reference is walked: the referenced entity's marked properties sit
  under the reference's property name. It is never a value itself, which would only
  render as an id.
- One hop only: a marked reference on a walked entity is not followed on, so the walk
  is bounded and cannot loop.
- A marked collection (a collection property or a back-reference) throws
  `CollectionCannotBeATemplateVariable`: it has no single entity to take values from.
- A null value stays null, so the resolver can tell "no value" from an unknown key.
- Any entity can be compiled; one with nothing marked gives an empty map.

**Rendering:**

```php
use Medas\Templates\TemplateResolver;

$text = service(TemplateResolver::class)->render(
    'Invoice {{ invoice:number }} comes to {{ invoice:total | currency }}.',
    ['invoice:number' => '2026-0042', 'invoice:total' => 4999],
    [],
    'nl-NL',
    Escaping::None,
);
```

The third argument holds defaults, as strings, for keys the values may not have.

**Adding a pipe:**

Implement `VariablePipe` on a `#[Service]` and give it an `apply()` method. Its first
two parameters are always the value and the locale; the parameters after them receive
the token's colon-separated arguments in order, each coerced to the parameter's type
(`int`, `float`, `bool` or `string`), with the parameter's default used when an
argument is left out. A variadic last parameter (`string ...$forms`) receives all the
remaining arguments. `apply()` returns a string, which the resolver escapes.

```php
use Medas\Core\Attributes\Service;
use Medas\Templates\VariablePipe;

#[Service]
readonly class UpperPipe implements VariablePipe
{
    public function name(): string
    {
        return 'upper';
    }

    public function apply(mixed $value, string $locale, int $length = 0): string
    {
        $upper = mb_strtoupper((string) $value, 'UTF-8');

        return $length > 0 ? mb_substr($upper, 0, $length) : $upper;
    }
}
```

Used as `{{ name | upper }}` or `{{ name | upper:3 }}`.

`apply()` is not part of the interface because each pipe has its own typed, defaulted
parameters; an interface method would have to declare one signature for all of them.
