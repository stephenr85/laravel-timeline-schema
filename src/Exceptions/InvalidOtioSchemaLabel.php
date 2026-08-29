<?php

namespace Rushing\TimelineSchema\Exceptions;

use Rushing\Popcorn\Registries\Exceptions\InvalidRegistryKey;

/**
 * A string offered as an `OTIO_SCHEMA` label that is not one.
 *
 * Extends the kernel's {@see InvalidRegistryKey} rather than a bare `InvalidArgumentException`, so a
 * caller that already catches malformed registry keys catches this one too: it is the same event —
 * a key the registry's door refused — reached through a foreign vocabulary's grammar instead of the
 * kernel's.
 */
class InvalidOtioSchemaLabel extends InvalidRegistryKey
{
    public function __construct(string $label)
    {
        parent::__construct(
            "`{$label}` is not an OTIO_SCHEMA label: expected a name followed by `.` and an integer "
                .'version, e.g. `Timeline.1` or `Clip.2`. The label is OpenTimelineIO\'s vocabulary and is '
                .'stored verbatim, so it is neither lowercased nor split at the dot — a dotted kernel '
                .'address is `Rushing\\Popcorn\\Registries\\Key`\'s.'
        );
    }
}
