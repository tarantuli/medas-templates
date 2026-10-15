<?php

declare(strict_types=1);

namespace Medas\Templates\Interfaces;

// A value that can name itself to a reader, in the reader's locale. The enum pipe
// asks for it: an enum's case name or backing value is not text for a reader, so the
// translation lives with the enum rather than in every template.
interface HasLabel
{
    public function label(string $locale): string;
}
