<?php

declare(strict_types=1);

namespace Rushing\TimelineSchema\Objects;

use Rushing\TimelineSchema\Contracts\OtioObject;
use Rushing\TimelineSchema\OtioHydrator;

/**
 * A lossless passthrough for OTIO objects whose `OTIO_SCHEMA` the registry does
 * not model (effects, transitions, vendor/NLE extension schemas, …). It stores
 * the raw array verbatim and re-emits it unchanged, so unfamiliar OTIO survives
 * hydrate → emit without a bespoke class per type — the OTIO analogue of
 * block-schema's GenericNode.
 *
 * Note: it preserves what it is given. Round-trip from a JSON string with empty
 * `{}` objects is the caller's concern (PHP decodes `{}` to `[]`); the typed core
 * objects use {@see OtioData::obj()} to keep their own emit byte-stable.
 */
final class GenericOtioObject implements OtioObject
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(private readonly array $data) {}

    public function schema(): string
    {
        $schema = $this->data['OTIO_SCHEMA'] ?? null;

        return is_string($schema) ? $schema : 'UnknownSchema.1';
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public static function fromOtio(array $data, OtioHydrator $hydrator): self
    {
        return new self($data);
    }
}
