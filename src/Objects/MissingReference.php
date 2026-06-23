<?php

declare(strict_types=1);

namespace Rushing\TimelineSchema\Objects;

use Rushing\TimelineSchema\Attributes\OtioField;
use Rushing\TimelineSchema\Attributes\OtioSchema;
use Rushing\TimelineSchema\OtioHydrator;

/**
 * The placeholder media reference: a clip whose OUTPUT has not been rendered or
 * attached yet. The default `media_reference` of a fresh {@see Clip}.
 */
#[OtioSchema('MissingReference.1', 'A placeholder for media not yet rendered/attached.')]
final class MissingReference extends OtioData
{
    public function __construct(
        #[OtioField('Optional placeholder name.', required: false)]
        public readonly string $name = '',
        #[OtioField('The available range, if known.', required: false)]
        public readonly ?TimeRange $available_range = null,
        #[OtioField('Free-form, namespaced OTIO metadata.', required: false)]
        public readonly array $metadata = [],
    ) {}

    public function toArray(): array
    {
        $out = [
            'OTIO_SCHEMA' => $this->schema(),
            'name' => $this->name,
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
            name: $data['name'] ?? '',
            available_range: isset($data['available_range'])
                ? TimeRange::fromOtio($data['available_range'], $hydrator)
                : null,
            metadata: (array) ($data['metadata'] ?? []),
        );
    }
}
