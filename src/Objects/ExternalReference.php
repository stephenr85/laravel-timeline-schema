<?php

declare(strict_types=1);

namespace Rushing\TimelineSchema\Objects;

use Rushing\TimelineSchema\Attributes\OtioField;
use Rushing\TimelineSchema\Attributes\OtioSchema;
use Rushing\TimelineSchema\OtioHydrator;

/**
 * A reference to external media by URL — a rendered clip's OUTPUT location.
 */
#[OtioSchema('ExternalReference.1', 'A reference to external media by URL.')]
final class ExternalReference extends OtioData
{
    public function __construct(
        #[OtioField('The media URL/URI.', example: 'file:///media/clip.wav')]
        public readonly string $target_url = '',
        #[OtioField('The available range of the media, if known.', required: false)]
        public readonly ?TimeRange $available_range = null,
        #[OtioField('Free-form, namespaced OTIO metadata.', required: false)]
        public readonly array $metadata = [],
    ) {}

    public function toArray(): array
    {
        $out = [
            'OTIO_SCHEMA' => $this->schema(),
            'target_url' => $this->target_url,
        ];

        if ($this->available_range !== null) {
            $out['available_range'] = $this->available_range->toArray();
        }

        $out['metadata'] = self::obj($this->metadata);

        return $out;
    }

    public static function fromOtio(array $data, OtioHydrator $hydrator): self
    {
        return new self(
            target_url: $data['target_url'] ?? '',
            available_range: isset($data['available_range'])
                ? TimeRange::fromOtio($data['available_range'], $hydrator)
                : null,
            metadata: (array) ($data['metadata'] ?? []),
        );
    }
}
