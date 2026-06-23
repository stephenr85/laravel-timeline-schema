<?php

declare(strict_types=1);

use Rushing\TimelineSchema\Contracts\SchemaRegistry;
use Rushing\TimelineSchema\Objects\Clip;
use Rushing\TimelineSchema\Objects\Marker;
use Rushing\TimelineSchema\Objects\Timeline;

it('resolves the built-in OTIO core types', function () {
    $registry = app(SchemaRegistry::class);

    expect($registry->has('Clip.2'))->toBeTrue()
        ->and($registry->resolve('Clip.2'))->toBe(Clip::class)
        ->and($registry->resolve('Timeline.1'))->toBe(Timeline::class)
        ->and($registry->resolve('Marker.2'))->toBe(Marker::class);
});

it('reports unknown schemas without throwing', function () {
    $registry = app(SchemaRegistry::class);

    expect($registry->has('Nope.9'))->toBeFalse()
        ->and($registry->resolve('Nope.9'))->toBeNull();
});

it('registers a custom class by reading its #[OtioSchema] label', function () {
    $registry = app(SchemaRegistry::class);
    $registry->registerClass(Clip::class);

    expect($registry->resolve('Clip.2'))->toBe(Clip::class);
});
