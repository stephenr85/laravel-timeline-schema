<?php

declare(strict_types=1);

namespace Rushing\TimelineSchema\Objects;

use Rushing\TimelineSchema\Attributes\OtioField;
use Rushing\TimelineSchema\Attributes\OtioSchema;
use Rushing\TimelineSchema\OtioHydrator;

/**
 * A marker annotating a `marked_range`. The unit a host uses to record section /
 * form boundaries on a dedicated form track. A leaf object — no children.
 */
#[OtioSchema('Marker.2', 'A marker annotating a range — the unit of section/form boundaries.')]
final class Marker extends OtioData
{
    public function __construct(
        #[OtioField('Marker label (e.g. the section name).', required: false)]
        public readonly string $name = '',
        #[OtioField('Marker color label, e.g. RED, GREEN.', example: 'RED', required: false)]
        public readonly string $color = 'RED',
        #[OtioField('The annotated range.', required: false)]
        public readonly ?TimeRange $marked_range = null,
        #[OtioField('Free-form comment.', required: false)]
        public readonly string $comment = '',
        #[OtioField('Free-form, namespaced OTIO metadata.', required: false)]
        public readonly array $metadata = [],
    ) {}

    public function toArray(): array
    {
        $out = [
            'OTIO_SCHEMA' => $this->schema(),
            'name' => $this->name,
            'color' => $this->color,
        ];

        if ($this->marked_range !== null) {
            $out['marked_range'] = $this->marked_range->toArray();
        }

        $out['comment'] = $this->comment;
        $out['metadata'] = self::obj($this->metadata);

        return $out;
    }

    public static function fromOtio(array $data, OtioHydrator $hydrator): self
    {
        return new self(
            name: $data['name'] ?? '',
            color: $data['color'] ?? 'RED',
            marked_range: isset($data['marked_range'])
                ? TimeRange::fromOtio($data['marked_range'], $hydrator)
                : null,
            comment: $data['comment'] ?? '',
            metadata: (array) ($data['metadata'] ?? []),
        );
    }
}
