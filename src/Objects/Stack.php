<?php

declare(strict_types=1);

namespace Rushing\TimelineSchema\Objects;

use Rushing\TimelineSchema\Attributes\OtioField;
use Rushing\TimelineSchema\Attributes\OtioSchema;
use Rushing\TimelineSchema\Contracts\OtioObject;
use Rushing\TimelineSchema\OtioHydrator;

/**
 * A stack of simultaneous tracks — the timeline body. `children` (Tracks) and
 * `markers` are polymorphic and managed outside the Data payload.
 */
#[OtioSchema('Stack.1', 'A stack of simultaneous tracks — the timeline body.')]
final class Stack extends OtioData
{
    /** @var list<OtioObject> */
    private array $children = [];

    /** @var list<OtioObject> */
    private array $markers = [];

    public function __construct(
        #[OtioField('Stack name (conventionally "tracks").', required: false)]
        public readonly string $name = 'tracks',
        #[OtioField('Optional trim window over the stack.', required: false)]
        public readonly ?TimeRange $source_range = null,
        #[OtioField('Free-form, namespaced OTIO metadata.', required: false)]
        public readonly array $metadata = [],
    ) {}

    /** @return list<OtioObject> */
    public function children(): array
    {
        return $this->children;
    }

    /** @param list<OtioObject> $children */
    public function withChildren(array $children): static
    {
        $clone = clone $this;
        $clone->children = array_values($children);

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

        if ($this->markers !== []) {
            $out['markers'] = array_map(fn (OtioObject $m): array => $m->toArray(), $this->markers);
        }

        $out['children'] = array_map(fn (OtioObject $c): array => $c->toArray(), $this->children);

        return $out;
    }

    public static function fromOtio(array $data, OtioHydrator $hydrator): self
    {
        $stack = new self(
            name: $data['name'] ?? 'tracks',
            source_range: isset($data['source_range'])
                ? TimeRange::fromOtio($data['source_range'], $hydrator)
                : null,
            metadata: (array) ($data['metadata'] ?? []),
        );

        return $stack
            ->withChildren(array_map(fn (array $c): OtioObject => $hydrator->hydrate($c), $data['children'] ?? []))
            ->withMarkers(array_map(fn (array $m): OtioObject => $hydrator->hydrate($m), $data['markers'] ?? []));
    }
}
