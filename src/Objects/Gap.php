<?php

declare(strict_types=1);

namespace Rushing\TimelineSchema\Objects;

use Rushing\TimelineSchema\Attributes\OtioField;
use Rushing\TimelineSchema\Attributes\OtioSchema;
use Rushing\TimelineSchema\Contracts\OtioObject;
use Rushing\TimelineSchema\OtioHydrator;

/**
 * An empty span on a track — silence/black between clips.
 */
#[OtioSchema('Gap.1', 'An empty span on a track (silence/black between clips).')]
final class Gap extends OtioData
{
    /** @var list<OtioObject> */
    private array $markers = [];

    public function __construct(
        #[OtioField('Gap name.', required: false)]
        public readonly string $name = '',
        #[OtioField('The gap duration window.', required: false)]
        public readonly ?TimeRange $source_range = null,
        #[OtioField('Free-form, namespaced OTIO metadata.', required: false)]
        public readonly array $metadata = [],
    ) {}

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

        if ($this->markers !== []) {
            $out['markers'] = array_map(fn (OtioObject $m): array => $m->toArray(), $this->markers);
        }

        return $out;
    }

    public static function fromOtio(array $data, OtioHydrator $hydrator): self
    {
        $gap = new self(
            name: $data['name'] ?? '',
            source_range: isset($data['source_range'])
                ? TimeRange::fromOtio($data['source_range'], $hydrator)
                : null,
            metadata: (array) ($data['metadata'] ?? []),
        );

        return $gap->withMarkers(
            array_map(fn (array $m): OtioObject => $hydrator->hydrate($m), $data['markers'] ?? [])
        );
    }
}
