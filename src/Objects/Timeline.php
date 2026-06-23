<?php

declare(strict_types=1);

namespace Rushing\TimelineSchema\Objects;

use Rushing\TimelineSchema\Attributes\OtioField;
use Rushing\TimelineSchema\Attributes\OtioSchema;
use Rushing\TimelineSchema\OtioHydrator;

/**
 * The root OTIO object — a named timeline wrapping a single tracks {@see Stack}.
 * `tracks` is managed outside the Data payload (it's a polymorphic child), so
 * Spatie never tries to hydrate it.
 */
#[OtioSchema('Timeline.1', 'The root OTIO object: a named timeline wrapping a tracks Stack.')]
final class Timeline extends OtioData
{
    private Stack $tracks;

    public function __construct(
        #[OtioField('Human-readable timeline name.', required: false)]
        public readonly string $name = '',
        #[OtioField('Optional global/wall-clock start time.', required: false)]
        public readonly ?RationalTime $global_start_time = null,
        #[OtioField('Free-form, namespaced OTIO metadata.', required: false)]
        public readonly array $metadata = [],
    ) {
        $this->tracks = new Stack;
    }

    public function tracks(): Stack
    {
        return $this->tracks;
    }

    public function withTracks(Stack $tracks): static
    {
        $clone = clone $this;
        $clone->tracks = $tracks;

        return $clone;
    }

    public function toArray(): array
    {
        $out = [
            'OTIO_SCHEMA' => $this->schema(),
            'name' => $this->name,
            'metadata' => self::obj($this->metadata),
        ];

        if ($this->global_start_time !== null) {
            $out['global_start_time'] = $this->global_start_time->toArray();
        }

        $out['tracks'] = $this->tracks->toArray();

        return $out;
    }

    public static function fromOtio(array $data, OtioHydrator $hydrator): self
    {
        $timeline = new self(
            name: $data['name'] ?? '',
            global_start_time: isset($data['global_start_time'])
                ? RationalTime::fromOtio($data['global_start_time'], $hydrator)
                : null,
            metadata: (array) ($data['metadata'] ?? []),
        );

        /** @var Stack $tracks */
        $tracks = $hydrator->hydrate($data['tracks'] ?? ['OTIO_SCHEMA' => 'Stack.1', 'children' => []]);

        return $timeline->withTracks($tracks);
    }
}
