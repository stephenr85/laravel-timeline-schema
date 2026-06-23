<?php

declare(strict_types=1);

namespace Rushing\TimelineSchema;

use Rushing\TimelineSchema\Contracts\OtioObject;
use Rushing\TimelineSchema\Contracts\SchemaRegistry;
use Rushing\TimelineSchema\Objects\GenericOtioObject;

/**
 * The inverse of {@see OtioObject::toArray()}: resolves an OTIO array's
 * `OTIO_SCHEMA` to a typed class and delegates to its `fromOtio()`, recursing
 * into polymorphic children. Labels the registry doesn't know fall through to a
 * lossless {@see GenericOtioObject} so unfamiliar OTIO survives the round-trip.
 */
final class OtioHydrator
{
    public function __construct(private readonly SchemaRegistry $registry) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function hydrate(array $data): OtioObject
    {
        $schema = $data['OTIO_SCHEMA'] ?? null;

        if (! is_string($schema) || ! $this->registry->has($schema)) {
            return GenericOtioObject::fromOtio($data, $this);
        }

        /** @var class-string<OtioObject> $class */
        $class = $this->registry->resolve($schema);

        return $class::fromOtio($data, $this);
    }
}
