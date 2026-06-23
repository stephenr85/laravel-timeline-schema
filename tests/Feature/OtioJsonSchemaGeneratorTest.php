<?php

declare(strict_types=1);

use Rushing\TimelineSchema\Objects\Clip;
use Rushing\TimelineSchema\Schema\OtioJsonSchemaGenerator;

it('projects an OTIO object to JSON Schema with its authoring metadata', function () {
    $schema = (new OtioJsonSchemaGenerator)->generate(new ReflectionClass(Clip::class));

    expect($schema['type'])->toBe('object')
        ->and($schema['description'])->toContain('clip')
        ->and($schema['properties'])->toHaveKeys(['name', 'source_range', 'metadata']);

    // source_range resolves to the TimeRange definition.
    expect($schema['properties']['source_range'])->toHaveKey('$ref');
    expect($schema['$defs'])->toHaveKey('TimeRange');
});

it('only claims OtioData subclasses', function () {
    $generator = new OtioJsonSchemaGenerator;

    expect($generator->canGenerate(new ReflectionClass(Clip::class)))->toBeTrue()
        ->and($generator->canGenerate(new ReflectionClass(\Spatie\LaravelData\Data::class)))->toBeFalse();
});

it('emits a strict LLM schema with x-* stripped and the object closed', function () {
    $schema = (new OtioJsonSchemaGenerator)->forLlmStrict()->generate(new ReflectionClass(Clip::class));

    expect($schema['additionalProperties'])->toBeFalse()
        ->and($schema['required'])->toContain('name', 'source_range', 'metadata')
        ->and($schema)->not->toHaveKey('title');

    // Optional-by-default properties become nullable in strict mode.
    expect($schema['properties']['name']['type'])->toContain('null');

    // No vendor x-* annotation survives into the LLM-facing contract.
    expect(json_encode($schema))->not->toContain('"x-');
});
