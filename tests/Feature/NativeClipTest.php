<?php

use Rushing\TimelineSchema\Objects\Clip;
use Rushing\TimelineSchema\Objects\ExternalReference;
use Rushing\TimelineSchema\OtioHydrator;

it('reads native OTIO 0.18 multiple references and preserves inactive media when editing the active one', function () {
    $native = json_decode(file_get_contents(__DIR__.'/../fixtures/native-clip-v2.otio'), true, flags: JSON_THROW_ON_ERROR);
    $clip = app(OtioHydrator::class)->hydrate($native);
    expect($clip)->toBeInstanceOf(Clip::class)
        ->and($clip->mediaReference()->target_url)->toBe('file:///proxy.mov');
    $edited = $clip->withMediaReference(new ExternalReference('file:///edited-proxy.mov'))->toArray();
    expect($edited['active_media_reference_key'])->toBe('proxy')
        ->and($edited['media_references']['camera']['target_url'])->toBe('file:///camera.mov')
        ->and($edited['media_references']['proxy']['target_url'])->toBe('file:///edited-proxy.mov')
        ->and($edited)->not->toHaveKey('media_reference')
        ->and($clip->mediaReference()->target_url)->toBe('file:///proxy.mov');
});

it('upgrades the previously emitted singular reference without dropping its target', function () {
    $clip = app(OtioHydrator::class)->hydrate(['OTIO_SCHEMA' => 'Clip.2', 'media_reference' => ['OTIO_SCHEMA' => 'ExternalReference.1', 'target_url' => 'file:///legacy.wav']]);
    $canonical = $clip->toArray();
    expect($canonical['media_references']['DEFAULT_MEDIA']['target_url'])->toBe('file:///legacy.wav')
        ->and($canonical['active_media_reference_key'])->toBe('DEFAULT_MEDIA');
});

it('rejects an active reference that does not exist instead of silently choosing different media', function () {
    $native = json_decode(file_get_contents(__DIR__.'/../fixtures/native-clip-v2.otio'), true, flags: JSON_THROW_ON_ERROR);
    $native['active_media_reference_key'] = 'missing';
    expect(fn () => app(OtioHydrator::class)->hydrate($native))->toThrow(InvalidArgumentException::class);
});
