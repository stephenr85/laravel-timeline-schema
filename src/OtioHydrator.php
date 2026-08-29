<?php

namespace Rushing\TimelineSchema;

use Rushing\TimelineSchema\Contracts\OtioObject;
use Rushing\TimelineSchema\Objects\GenericOtioObject;
use Rushing\TimelineSchema\Schema\OtioSchemaRegistry;

/**
 * The inverse of {@see OtioObject::toArray()}: resolves an OTIO array's
 * `OTIO_SCHEMA` to a typed class and delegates to its `fromOtio()`, recursing
 * into polymorphic children. Labels the registry doesn't know fall through to a
 * lossless {@see GenericOtioObject} so unfamiliar OTIO survives the round-trip.
 */
class OtioHydrator
{
    public function __construct(private OtioSchemaRegistry $registry) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function hydrate(array $data): OtioObject
    {
        $schema = $data['OTIO_SCHEMA'] ?? null;

        $class = is_string($schema) ? $this->registry->classFor($schema) : null;

        // ⚠️ `classFor()`, not `resolve()`. The kernel's `resolve()` THROWS on a miss; this path is
        // reached with whatever the document happened to carry, and an unknown label is a lossless
        // passthrough here rather than an error. See OtioSchemaRegistry's docblock.
        if ($class === null) {
            return GenericOtioObject::fromOtio($data, $this);
        }

        return $class::fromOtio($data, $this);
    }
}
