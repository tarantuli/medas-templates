<?php

declare(strict_types=1);

namespace Medas\Templates\Attributes;

// Marks an entity property as exposable to templates. Exposure is opt-in per field:
// only marked properties are compiled into template variables, so a property being
// readable by the API does not by itself make it available in a customer-facing
// e-mail or task.
//
// On a scalar the value itself is exposed. On a to-one reference, the referenced
// entity's own marked properties are exposed under the reference's name (e.g.
// "tenant:name"), when the marking entity is the template subject. One hop only: a
// marked reference on that referenced entity is not followed on. A collection cannot
// be marked; compiling an entity that marks one throws.
//
// Multi-hop could later be an argument here naming the sub-paths to follow from this
// reference; nothing needs it yet.
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class IsTemplateVariable
{
}
