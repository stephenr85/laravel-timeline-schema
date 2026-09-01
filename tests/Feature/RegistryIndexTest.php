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

it('records the DECLARING class as the registrant, because there is no describing provider any more', function () {
    // ⚠️ This asserted `TimelineSchemaServiceProvider::class` until registry-kernel 73's cutover, and the
    // expectation is obsolete rather than the behaviour being wrong: **there is no describing provider**.
    // Membership is now baked from the `#[IsRegistry]` on the class and resolved lazily, so the honest
    // registrant is the class that declared the root. A bake cannot know which provider would have
    // described it, and inventing one would be a worse answer than the true one.
    //
    // 29 D2 asked for a registrant vocabulary naming the package or provider; the bake is where that
    // becomes derivable from the file path, and it is deliberately not taken here — see ticket 73.
    expect(app(RegistryIndex::class)->registrantOf(Key::of('otio.schemas')))
        ->toBe(OtioSchemaRegistry::class);
})->skip(fn () => ! method_exists(RegistryIndex::class, 'registrantOf'), 'RecordsRegistrants not available in this popcorn.');
