<?php

use Rushing\TimelineSchema\Objects\Clip;
use Rushing\TimelineSchema\Objects\ExternalReference;
use Rushing\TimelineSchema\Objects\RationalTime;
use Rushing\TimelineSchema\Objects\Stack;
use Rushing\TimelineSchema\Objects\Timeline;
use Rushing\TimelineSchema\Objects\TimeRange;
use Rushing\TimelineSchema\Objects\Track;
use Rushing\TimelineSchema\OtioHydrator;

function minimalSongTimeline(): Timeline
{
    $range = new TimeRange(
        start_time: new RationalTime(0.0, 48000.0),
        duration: new RationalTime(96000.0, 48000.0),
    );

    $clip = (new Clip(name: 'verse', source_range: $range))
        ->withMediaReference(new ExternalReference('file:///stems/verse.wav'));

    $track = (new Track(name: 'lead vocal', kind: 'Audio'))->withChildren([$clip]);
    $stack = (new Stack)->withChildren([$track]);

    return (new Timeline(name: 'demo song'))->withTracks($stack);
}

it('emits a single-track song stack as valid OTIO with OTIO_SCHEMA discriminators', function () {
    $otio = minimalSongTimeline()->toArray();

    expect($otio['OTIO_SCHEMA'])->toBe('Timeline.1');
    expect($otio['tracks']['OTIO_SCHEMA'])->toBe('Stack.1');
    expect($otio['tracks']['children'][0]['OTIO_SCHEMA'])->toBe('Track.1');
    expect($otio['tracks']['children'][0]['kind'])->toBe('Audio');
    expect($otio['tracks']['children'][0]['children'][0]['OTIO_SCHEMA'])->toBe('Clip.2');
    expect($otio['tracks']['children'][0]['children'][0]['media_reference']['OTIO_SCHEMA'])->toBe('ExternalReference.1');
});

it('round-trips emit -> hydrate -> emit byte-identically', function () {
    $timeline = minimalSongTimeline();

    $a = json_encode($timeline->toArray());

    $hydrated = app(OtioHydrator::class)->hydrate(json_decode($a, true));
    $b = json_encode($hydrated->toArray());

    expect($hydrated)->toBeInstanceOf(Timeline::class);
    expect($b)->toBe($a);
});

it('reconstructs the typed tree on hydrate', function () {
    $a = minimalSongTimeline()->toArray();

    /** @var Timeline $hydrated */
    $hydrated = app(OtioHydrator::class)->hydrate($a);

    $track = $hydrated->tracks()->children()[0];
    expect($track)->toBeInstanceOf(Track::class);

    $clip = $track->children()[0];
    expect($clip)->toBeInstanceOf(Clip::class)
        ->and($clip->mediaReference())->toBeInstanceOf(ExternalReference::class)
        ->and($clip->mediaReference()->target_url)->toBe('file:///stems/verse.wav');
});

it('keeps empty metadata as a JSON object, not an array', function () {
    $json = json_encode(minimalSongTimeline()->toArray());

    expect($json)->toContain('"metadata":{}')
        ->and($json)->not->toContain('"metadata":[]');
});
