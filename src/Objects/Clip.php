<?php

declare(strict_types=1);

namespace Rushing\TimelineSchema\Objects;

use Rushing\TimelineSchema\Attributes\OtioField;
use Rushing\TimelineSchema\Attributes\OtioSchema;
use Rushing\TimelineSchema\Contracts\OtioObject;
use Rushing\TimelineSchema\OtioHydrator;

/**
 * A single clip referencing media over a `source_range`. The split a host relies
 * on: `media_reference` is the rendered OUTPUT, while extension truth rides
 * `metadata` under the host's configured namespace — the two stay separable
 * through the round-trip. `media_reference` is polymorphic (External/Missing/…)
 * and managed outside the Data payload; it defaults to MissingReference.
 */
#[OtioSchema('Clip.2', 'A clip referencing media over a source time range.')]
final class Clip extends OtioData
{
    private OtioObject $media_reference;

    /** @var list<OtioObject> */
    private array $markers = [];

    public function __construct(
        #[OtioField('Clip name.', required: false)]
        public readonly string $name = '',
        #[OtioField('The clip window into its media.', required: false)]
        public readonly ?TimeRange $source_range = null,
        #[OtioField('Free-form, namespaced OTIO metadata (the host extension carrier).', required: false)]
        public readonly array $metadata = [],
    ) {
        $this->media_reference = new MissingReference;
    }

    public function mediaReference(): OtioObject
    {
        return $this->media_reference;
    }

    public function withMediaReference(OtioObject $reference): static
    {
        $clone = clone $this;
        $clone->media_reference = $reference;

        return $clone;
    }

    /** @return list<OtioObject> */
    public function markers(): array
    {
        return $this->markers;
    }

    /** @param list<OtioObject> $markers */
    public function withMarkers(array $markers): static
    {
        $clone = clone $this;
        $clone->markers = array_values($markers);

        return $clone;
    }

    public function toArray(): array
    {
        $out = [
            'OTIO_SCHEMA' => $this->schema(),
            'name' => $this->name,
            'metadata' => self::obj($this->metadata),
        ];

        if ($this->source_range !== null) {
            $out['source_range'] = $this->source_range->toArray();
        }

        $out['media_reference'] = $this->media_reference->toArray();

        if ($this->markers !== []) {
            $out['markers'] = array_map(fn (OtioObject $m): array => $m->toArray(), $this->markers);
        }

        return $out;
    }

    public static function fromOtio(array $data, OtioHydrator $hydrator): self
    {
        $clip = new self(
            name: $data['name'] ?? '',
            source_range: isset($data['source_range'])
                ? TimeRange::fromOtio($data['source_range'], $hydrator)
                : null,
            metadata: (array) ($data['metadata'] ?? []),
        );

        if (isset($data['media_reference']) && is_array($data['media_reference'])) {
            $clip = $clip->withMediaReference($hydrator->hydrate($data['media_reference']));
        }

        return $clip->withMarkers(
            array_map(fn (array $m): OtioObject => $hydrator->hydrate($m), $data['markers'] ?? [])
        );
    }
}
