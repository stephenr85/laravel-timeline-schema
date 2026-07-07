<?php

namespace Rushing\TimelineSchema\Objects;

use Rushing\TimelineSchema\Attributes\OtioField;
use Rushing\TimelineSchema\Attributes\OtioSchema;
use Rushing\TimelineSchema\OtioHydrator;

/**
 * OTIO's atomic time value: a sample/frame count `value` measured at a `rate`.
 * A value object hydrated structurally by its parents, not a top-level node.
 */
#[OtioSchema('RationalTime.1', 'A time value: a sample/frame count at a rate.')]
class RationalTime extends OtioData
{
    public function __construct(
        #[OtioField('The sample/frame index.', example: 0, required: false)]
        public float $value = 0.0,
        #[OtioField('The rate the value is counted at (fps or audio sample rate).', example: 24, required: false)]
        public float $rate = 24.0,
    ) {}

    public function toArray(): array
    {
        return [
            'OTIO_SCHEMA' => $this->schema(),
            'value' => $this->value,
            'rate' => $this->rate,
        ];
    }

    public static function fromOtio(array $data, OtioHydrator $hydrator): self
    {
        return new self(
            value: (float) ($data['value'] ?? 0.0),
            rate: (float) ($data['rate'] ?? 24.0),
        );
    }
}
