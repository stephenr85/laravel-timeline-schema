<?php

use Rushing\Popcorn\Registries\Exceptions\RegistryMiss;
use Rushing\TimelineSchema\Exceptions\InvalidOtioSchemaLabel;
use Rushing\TimelineSchema\Objects\Clip;
use Rushing\TimelineSchema\Objects\Marker;
use Rushing\TimelineSchema\Objects\Timeline;
use Rushing\TimelineSchema\Schema\OtioSchemaRegistry;

it('resolves the built-in OTIO core types', function () {
    $registry = app(OtioSchemaRegistry::class);

    expect($registry->has('Clip.2'))->toBeTrue()
        ->and($registry->classFor('Clip.2'))->toBe(Clip::class)
        ->and($registry->classFor('Timeline.1'))->toBe(Timeline::class)
        ->and($registry->classFor('Marker.2'))->toBe(Marker::class);
});

it('reports unknown schemas without throwing', function () {
    $registry = app(OtioSchemaRegistry::class);

    expect($registry->has('Nope.9'))->toBeFalse()
        ->and($registry->classFor('Nope.9'))->toBeNull();
});

it('registers a custom class by reading its #[OtioSchema] label', function () {
    $registry = app(OtioSchemaRegistry::class);
    $registry->registerClass(Clip::class);

    expect($registry->classFor('Clip.2'))->toBe(Clip::class);
});

/*
 * ---------------------------------------------------------------------------
 * The kernel conversion. Every case below fails against the pre-conversion
 * class — either fatally (the method or class did not exist) or on its value.
 * ---------------------------------------------------------------------------
 */

it('renders keys as the OTIO label verbatim, not as a root-stamped address', function () {
    // The `NavKindRegistry` hazard, which a type checker cannot see: a root-stamped key renders as
    // `otio.schemas.timeline.1` and still satisfies `array<string, class-string>`. Nothing but an
    // exact-value assertion catches it. `OtioSchemaKey` is not `Rootable`, so the door leaves it alone.
    $all = app(OtioSchemaRegistry::class)->all();

    expect(array_keys($all))->toContain('Timeline.1', 'Clip.2', 'ExternalReference.1')
        ->and($all['Timeline.1'])->toBe(Timeline::class);

    foreach (array_keys($all) as $label) {
        expect($label)->toMatch('/^[A-Z]/');
    }
});

it('separates the throwing resolve() from the nullable classFor()', function () {
    // The rename is the whole point: `resolve()` used to return null on a miss and the hydrator read
    // that null as "fall back to the passthrough". Same name, opposite behaviour, no type-level tell.
    $registry = app(OtioSchemaRegistry::class);

    expect($registry->classFor('Nope.9'))->toBeNull()
        ->and($registry->tryResolve('Nope.9'))->toBeNull();

    expect(fn () => $registry->resolve('Nope.9'))->toThrow(RegistryMiss::class);
});

it('answers has() false for a malformed label rather than throwing', function () {
    // `has()` is asked about labels read straight out of `.otio` JSON. A bare delegation to the kernel
    // door turns a validation question into a 500 — registry-kernel 58's second-order finding.
    $registry = app(OtioSchemaRegistry::class);

    expect($registry->has('not a label'))->toBeFalse()
        ->and($registry->classFor('not a label'))->toBeNull();
});

it('throws on a malformed label at the register door, where the author owns the spelling', function () {
    // The mirror of the case above, and the line the ecosystem AGENTS.md draws: a label a REGISTRANT
    // supplies is grammar its author could have gotten right, so it throws; a label a DOCUMENT supplies
    // is a miss.
    expect(fn () => app(OtioSchemaRegistry::class)->register('timeline', Timeline::class))
        ->toThrow(InvalidOtioSchemaLabel::class);
});

it('keeps register() source-compatible with the two-argument call it replaced', function () {
    $registry = app(OtioSchemaRegistry::class);

    expect($registry->register('Custom.1', Timeline::class))->toBe($registry)
        ->and($registry->classFor('Custom.1'))->toBe(Timeline::class);
});
