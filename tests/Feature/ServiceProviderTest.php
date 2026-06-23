<?php

declare(strict_types=1);

use Rushing\TimelineSchema\Contracts\OtioValidator;
use Rushing\TimelineSchema\Contracts\SchemaRegistry;
use Rushing\TimelineSchema\OtioHydrator;
use Rushing\TimelineSchema\Schema\OtioSchemaRegistry;
use Rushing\TimelineSchema\Validation\NullOtioValidator;

it('binds the registry, hydrator and validator', function () {
    expect(app(SchemaRegistry::class))->toBeInstanceOf(OtioSchemaRegistry::class)
        ->and(app(OtioHydrator::class))->toBeInstanceOf(OtioHydrator::class)
        ->and(app(OtioValidator::class))->toBeInstanceOf(NullOtioValidator::class);
});

it('defaults to a non-semantic PHP-native validator', function () {
    $result = app(OtioValidator::class)->validate(['OTIO_SCHEMA' => 'Timeline.1']);

    expect($result->valid)->toBeTrue()
        ->and($result->semantic)->toBeFalse();
});
