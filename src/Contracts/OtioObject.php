<?php

namespace Rushing\TimelineSchema\Contracts;

use Rushing\TimelineSchema\OtioHydrator;

/**
 * One node in an OTIO tree. Unlike a ProseMirror node (uniform `{type, attrs,
 * content}`), OTIO objects are heterogeneous — each carries its own typed fields
 * (a Clip has `media_reference`, a Track has `kind` + `children`) — so this
 * contract deliberately does NOT reuse block-schema's `Node`. The discriminator
 * is the embedded `OTIO_SCHEMA` label.
 */
interface OtioObject
{
    /** The `OTIO_SCHEMA` discriminator, e.g. "Clip.2". */
    public function schema(): string;

    /**
     * The OTIO JSON array for this object, `OTIO_SCHEMA` included. Total and
     * rebuildable — the inverse of {@see fromOtio()}.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Reconstruct a typed object from its OTIO array, recursing into polymorphic
     * children through the hydrator.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromOtio(array $data, OtioHydrator $hydrator): self;
}
