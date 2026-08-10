> You are in **rushing/laravel-timeline-schema** — a faithful OpenTimelineIO (OTIO) document model for Laravel: typed Data node objects discriminated by OTIO_SCHEMA, lossless .otio JSON round-trip, and JSON Schema projection.

This is a standalone PHP Composer package. It is medium- and app-agnostic; extension metadata rides a configurable namespace.

## Vendored family-package conventions

Any repo that vendors another family repo's code (composer `vendor/<vendor>/<pkg>/`, npm
`node_modules/<vendor>/<pkg>/`) checks that vendored repo's own `AGENTS.md` for conventions it
ships with itself before editing through into it.
