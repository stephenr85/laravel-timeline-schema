<?php

use Rushing\Popcorn\Registries\IsRegistry;
use Rushing\Popcorn\Registries\Key;
use Rushing\Popcorn\Registries\RegistryIndex;
use Rushing\TimelineSchema\Contracts\OtioObject;
use Rushing\TimelineSchema\Schema\OtioSchemaRegistry;
use Rushing\TimelineSchema\TimelineSchemaServiceProvider;

it('binds ONE RegistryIndex, so the provider seeds the object consumers read', function () {
    // ⚠️ The estate's seven-instance testbench trap, asserted as identity rather than existence.
    // `RegistryIndex` is auto-resolvable, so without `PopcornServiceProvider` in getPackageProviders()
    // this line is false, `describe()` writes to a throwaway object, and every other assertion in this
    // file still passes against an index nobody reads.
    expect(app(RegistryIndex::class))->toBe(app(RegistryIndex::class));

    expect(app(OtioSchemaRegistry::class))->toBe(app(OtioSchemaRegistry::class));
});

it('describes otio.schemas into the index at boot', function () {
    $index = app(RegistryIndex::class);

    expect($index->has(Key::of('otio.schemas')))->toBeTrue()
        ->and($index->resolve(Key::of('otio.schemas')))->toBe(app(OtioSchemaRegistry::class));
});

it('declares a complete #[IsRegistry] on the class that owns the keyspace', function () {
    $declaration = IsRegistry::of(app(OtioSchemaRegistry::class));

    expect($declaration->root)->toBe('otio.schemas')
        ->and($declaration->entryType)->toBe(OtioObject::class)
        ->and($declaration->arity)->not->toBeEmpty()
        ->and($declaration->of)->not->toBeEmpty();
});

it('records the describing provider as the registrant', function () {
    expect(app(RegistryIndex::class)->registrantOf(Key::of('otio.schemas')))
        ->toBe(TimelineSchemaServiceProvider::class);
})->skip(fn () => ! method_exists(RegistryIndex::class, 'registrantOf'), 'RecordsRegistrants not available in this popcorn.');
