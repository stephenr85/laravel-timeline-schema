<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Extension metadata namespace
    |--------------------------------------------------------------------------
    |
    | OTIO's `metadata` field is a free-form, namespaced dictionary. Tools stash
    | their own data under a single reserved key so it never collides with other
    | tools' namespaces (e.g. `fcp`, `resolve`, `nuke`). This package stays
    | application-agnostic: set this to YOUR app's slug and the ExtensionMetadata
    | helper reads/writes your truth under that key. Null disables the helper —
    | the package still round-trips `metadata` verbatim regardless.
    |
    */

    'metadata_namespace' => env('TIMELINE_SCHEMA_METADATA_NAMESPACE'),

];
