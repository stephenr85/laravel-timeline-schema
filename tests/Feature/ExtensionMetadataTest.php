<?php

declare(strict_types=1);

use Rushing\TimelineSchema\Support\ExtensionMetadata;

it('reads and writes extension truth under the configured namespace', function () {
    config()->set('timeline-schema.metadata_namespace', 'splicewire');

    $metadata = ExtensionMetadata::put(
        ['fcp' => ['marker' => 1]],
        ['slots' => ['lyric' => 'hello']],
    );

    expect($metadata)->toBe([
        'fcp' => ['marker' => 1],
        'splicewire' => ['slots' => ['lyric' => 'hello']],
    ]);

    expect(ExtensionMetadata::get($metadata))->toBe(['slots' => ['lyric' => 'hello']]);
});

it('honors whatever namespace the host configures — no hardcoded app name', function () {
    config()->set('timeline-schema.metadata_namespace', 'acme');

    $metadata = ExtensionMetadata::put([], ['take' => 3]);

    expect($metadata)->toBe(['acme' => ['take' => 3]]);
});

it('is inert when no namespace is configured', function () {
    config()->set('timeline-schema.metadata_namespace', null);

    expect(ExtensionMetadata::namespace())->toBeNull()
        ->and(ExtensionMetadata::get(['splicewire' => ['a' => 1]]))->toBe([]);
});

it('refuses to write extension metadata without a namespace', function () {
    config()->set('timeline-schema.metadata_namespace', null);

    ExtensionMetadata::put([], ['a' => 1]);
})->throws(LogicException::class);
