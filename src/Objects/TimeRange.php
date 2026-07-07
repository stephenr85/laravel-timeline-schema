<?php

namespace Rushing\TimelineSchema\Objects;

use Rushing\TimelineSchema\Attributes\OtioField;
use Rushing\TimelineSchema\Attributes\OtioSchema;
use Rushing\TimelineSchema\OtioHydrator;

/**
 * A span: a `start_time` plus a `duration`, both RationalTimes. The native unit
 * of `source_range` (a clip's window into its media) and `marked_range`.
 */
#[OtioSchema('TimeRange.1', 'A span: start_time + duration, both RationalTimes.')]
class TimeRange extends OtioData
{
    public function __construct(
        #[OtioField('Where the span starts.', required: false)]
        public RationalTime $start_time = new RationalTime,
        #[OtioField('How long the span lasts.', required: false)]
        public RationalTime $duration = new RationalTime,
    ) {}

    public function toArray(): array
    {
        return [
            'OTIO_SCHEMA' => $this->schema(),
            'duration' => $this->duration->toArray(),
            'start_time' => $this->start_time->toArray(),
        ];
    }

    public static function fromOtio(array $data, OtioHydrator $hydrator): self
    {
        return new self(
            start_time: RationalTime::fromOtio($data['start_time'] ?? [], $hydrator),
            duration: RationalTime::fromOtio($data['duration'] ?? [], $hydrator),
        );
    }
}
