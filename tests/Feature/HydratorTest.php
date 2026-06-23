<?php

declare(strict_types=1);

use Rushing\TimelineSchema\Objects\GenericOtioObject;
use Rushing\TimelineSchema\Objects\Track;
use Rushing\TimelineSchema\OtioHydrator;

it('passes an unknown OTIO schema through losslessly', function () {
    $raw = [
        'OTIO_SCHEMA' => 'Effect.1',
        'name' => 'blur',
        'effect_name' => 'Gaussian',
        'metadata' => ['amount' => 5],
    ];

    $object = app(OtioHydrator::class)->hydrate($raw);

    expect($object)->toBeInstanceOf(GenericOtioObject::class)
        ->and($object->schema())->toBe('Effect.1')
        ->and($object->toArray())->toBe($raw);
});

it('preserves an unknown child nested inside a known track', function () {
    $raw = [
        'OTIO_SCHEMA' => 'Track.1',
        'name' => 'fx',
        'kind' => 'Video',
        'metadata' => ['note' => 'keep me'],
        'children' => [
            ['OTIO_SCHEMA' => 'Transition.1', 'transition_type' => 'SMPTE_Dissolve'],
        ],
    ];

    /** @var Track $track */
    $track = app(OtioHydrator::class)->hydrate($raw);

    expect($track)->toBeInstanceOf(Track::class)
        ->and($track->children()[0])->toBeInstanceOf(GenericOtioObject::class)
        ->and($track->toArray()['children'][0])->toBe($raw['children'][0]);
});

it('falls back to a generic object when OTIO_SCHEMA is missing', function () {
    $object = app(OtioHydrator::class)->hydrate(['name' => 'orphan']);

    expect($object)->toBeInstanceOf(GenericOtioObject::class)
        ->and($object->schema())->toBe('UnknownSchema.1');
});
