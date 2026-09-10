<?php

namespace Rushing\TimelineSchema\Objects;

use Rushing\TimelineSchema\Attributes\OtioField;
use Rushing\TimelineSchema\Attributes\OtioSchema;
use Rushing\TimelineSchema\Contracts\OtioObject;
use Rushing\TimelineSchema\OtioHydrator;

/**
 * A single clip referencing media over a `source_range`. The split a host relies
 * on: the active media reference is the rendered OUTPUT, while extension truth rides
 * `metadata` under the host's configured namespace — the two stay separable
 * through the round-trip. Clip.2 serializes a map of references and an active key.
 * The single-reference accessor selects that key; inactive references survive edits.
 */
#[OtioSchema('Clip.2', 'A clip referencing media over a source time range.')]
class Clip extends OtioData
{
    /** @var array<string, OtioObject> */
    private array $media_references;

    private string $active_media_reference_key = 'DEFAULT_MEDIA';

    /** @var list<OtioObject> */
    private array $markers = [];

    public function __construct(
        #[OtioField('Clip name.', required: false)]
        public string $name = '',
        #[OtioField('The clip window into its media.', required: false)]
        public ?TimeRange $source_range = null,
        #[OtioField('Free-form, namespaced OTIO metadata (the host extension carrier).', required: false)]
        public array $metadata = [],
    ) {
        $this->media_references = ['DEFAULT_MEDIA' => new MissingReference];
    }

    public function mediaReference(): OtioObject
    {
        return $this->media_references[$this->active_media_reference_key];
    }

    public function withMediaReference(OtioObject $reference): static
    {
        $clone = clone $this;
        $clone->media_references[$this->active_media_reference_key] = $reference;

        return $clone;
    }

    /** @param array<string, OtioObject> $references */
    public function withMediaReferences(array $references, string $activeKey): static
    {
        if ($activeKey === '' || ! isset($references[$activeKey])) {
            throw new \InvalidArgumentException('The active media reference must name a reference in the map.');
        }
        foreach ($references as $key => $reference) {
            if (! is_string($key) || $key === '' || ! $reference instanceof OtioObject) {
                throw new \InvalidArgumentException('Media references require nonempty string keys and OTIO objects.');
            }
        }
        $clone = clone $this;
        $clone->media_references = $references;
        $clone->active_media_reference_key = $activeKey;

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

        $out['media_references'] = array_map(fn (OtioObject $reference): array => $reference->toArray(), $this->media_references);
        $out['active_media_reference_key'] = $this->active_media_reference_key;

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

        if (isset($data['media_references']) && is_array($data['media_references'])) {
            $references = array_map(fn (array $reference): OtioObject => $hydrator->hydrate($reference), $data['media_references']);
            $clip = $clip->withMediaReferences($references, $data['active_media_reference_key'] ?? 'DEFAULT_MEDIA');
        } elseif (isset($data['media_reference']) && is_array($data['media_reference'])) {
            // Read documents emitted before the Clip.2 serializer correction; always emit canonical Clip.2.
            $clip = $clip->withMediaReference($hydrator->hydrate($data['media_reference']));
        }

        return $clip->withMarkers(
            array_map(fn (array $m): OtioObject => $hydrator->hydrate($m), $data['markers'] ?? [])
        );
    }
}
