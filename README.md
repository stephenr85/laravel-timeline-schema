# rushing/laravel-timeline-schema

A faithful [OpenTimelineIO](https://opentimeline.io) (OTIO) document model for Laravel. Every object
in an `.otio` document becomes a typed `spatie/laravel-data` node, chosen by its `OTIO_SCHEMA` label,
and writes back out to the same JSON it was read from. Objects of a type the package doesn't know
are kept as they are, so a document written by another tool survives a round trip unchanged. The
package is medium- and app-agnostic; `OtioJsonSchemaGenerator` projects the node classes to JSON
Schema.

## Installation

```bash
composer require rushing/laravel-timeline-schema
```

The service provider is auto-discovered. The config file is optional, and sets the namespace your
application uses inside OTIO's free-form `metadata` field:

```bash
php artisan vendor:publish --tag=timeline-schema-config
```

## Example

```php
use Rushing\TimelineSchema\OtioHydrator;

$raw = json_decode(file_get_contents('edit.otio'), true);

$timeline = app(OtioHydrator::class)->hydrate($raw);   // a typed Timeline, Stack, Track, Clip… tree

$otio = $timeline->toArray();                          // back to OTIO JSON, unknown objects verbatim
```

## Registries

This package declares one registry:

- [`otio.schemas`](docs/registries/otio.schemas.mdx): the map from an `OTIO_SCHEMA` label, such as
  `Clip.2`, to the class that models it. The package registers the OTIO core types; your own types
  register here too.

## Tests

```bash
composer install
vendor/bin/pest
```

## License

MIT
