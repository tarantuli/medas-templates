<?php

declare(strict_types=1);

namespace Medas\Templates;

// How a rendered token is escaped for the document it lands in. Chosen per render
// call, not per token, so a value cannot reach an HTML document unescaped because an
// author forgot a pipe. A token that ends in "| raw" is left as it is: for a value
// known to be safe markup.
enum Escaping
{
    // Plain text, such as an e-mail subject or a task description.
    case None;

    // HTML, such as an e-mail body: & < > " and ' become entities.
    case Html;
}
