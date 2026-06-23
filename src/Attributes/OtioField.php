<?php

declare(strict_types=1);

namespace Rushing\TimelineSchema\Attributes;

use Attribute;

/**
 * Authoring metadata for an OTIO object property, bridged into the JSON Schema
 * projection (description / example / required). Mirrors block-schema's NodeAttr.
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final class OtioField
{
    public function __construct(
        public readonly ?string $description = null,
        public readonly mixed $example = null,
        public readonly bool $required = true,
    ) {}
}
