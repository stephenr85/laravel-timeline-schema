<?php

namespace Rushing\TimelineSchema\Attributes;

use Attribute;

/**
 * Authoring metadata for an OTIO object property, bridged into the JSON Schema
 * projection (description / example / required). Mirrors block-schema's NodeAttr.
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class OtioField
{
    public function __construct(
        public ?string $description = null,
        public mixed $example = null,
        public bool $required = true,
    ) {}
}
