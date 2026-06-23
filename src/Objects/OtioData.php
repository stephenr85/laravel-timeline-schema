<?php

declare(strict_types=1);

namespace Rushing\TimelineSchema\Objects;

use LogicException;
use ReflectionClass;
use Rushing\TimelineSchema\Attributes\OtioSchema;
use Rushing\TimelineSchema\Contracts\OtioObject;
use Spatie\LaravelData\Data;

/**
 * Base for the built-in OTIO objects: a Spatie Data class (so its typed
 * properties drive the JSON Schema projection and `::from()` ergonomics) that
 * carries an #[OtioSchema] discriminator. Polymorphic children (`children`,
 * `media_reference`, `tracks`) are NOT Data payload — each node manages them
 * explicitly and stitches them in {@see toArray()}, so Spatie never tries to
 * hydrate a heterogeneous OTIO union. Concrete nodes own `toArray()`/`fromOtio()`
 * to keep emit byte-stable.
 */
abstract class OtioData extends Data implements OtioObject
{
    public function schema(): string
    {
        $attrs = (new ReflectionClass(static::class))->getAttributes(OtioSchema::class);

        if (empty($attrs)) {
            throw new LogicException(static::class.' must declare an #[OtioSchema] attribute.');
        }

        return $attrs[0]->newInstance()->name;
    }

    /**
     * Emit an associative map as a JSON object, never a JSON array. An empty PHP
     * array encodes as `[]`; OTIO `metadata` must stay `{}` so emit→parse→emit is
     * byte-stable. Non-empty maps encode as objects already.
     *
     * @param  array<string, mixed>  $map
     */
    protected static function obj(array $map): object|array
    {
        return $map === [] ? new \stdClass : $map;
    }
}
