<?php

namespace Rushing\TimelineSchema\Attributes;

use Attribute;

/**
 * Declares an OTIO object's `OTIO_SCHEMA` discriminator — the versioned schema
 * label OTIO embeds in every object (e.g. "Clip.2", "Timeline.1"). The label is
 * BOTH the type name and its schema version; the registry resolves it back to a
 * class on hydrate. `description` feeds the JSON Schema projection.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class OtioSchema
{
    public function __construct(
        public string $name,
        public ?string $description = null,
    ) {}
}
