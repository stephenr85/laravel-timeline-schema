# 0001 — OTIO-native node model, round-trip, and package boundaries

Status: accepted (2026-06-23)

## Context

`laravel-timeline-schema` is the isolated OpenTimelineIO (OTIO) representation for
the composition engine — the audio/video analogue of how `laravel-block-schema`
wraps ProseMirror for content. `.otio` files are JSON, so the same "typed Data
node model + lossless JSON round-trip + JSON Schema projection" pattern applies.

Two structural questions had to be settled before writing nodes:

1. Could OTIO reuse `laravel-block-schema`'s `Node`/`Document`/`Schema` contracts?
2. Where does the kernel-facing `SpineBinding` (`Stack ↔ .otio`) and the host's
   cell↔Clip mapping live, given this package must stay a *dependency of* the
   composition engine, never the reverse?

## Decision

### 1. OTIO gets its own node model — not block-schema's contracts

ProseMirror nodes are uniform: every node is `{type, attrs, content}`. OTIO
objects are **heterogeneous** — each schema carries its own typed fields (a `Clip`
has `media_reference` + `source_range`; a `Track` has `kind` + `children`; a
`Marker` has `marked_range` + `color`) and is discriminated by an embedded,
*versioned* `OTIO_SCHEMA` label (`"Clip.2"`, `"Timeline.1"`). Forcing that into a
`{type, attrs, content}` shape would be a leaky abstraction.

So this package defines its own contract, `Contracts\OtioObject`
(`schema()` / `toArray()` / `fromOtio()`), and its own typed `Data` objects
(`Timeline`, `Stack`, `Track`, `Clip`, `Gap`, `Marker`, `ExternalReference`,
`MissingReference`) plus the `RationalTime` / `TimeRange` value objects. Only the
**Data → JSON Schema** layer is shared, via a hard dependency on
`laravel-data-schemas` (`OtioJsonSchemaGenerator extends JsonSchemaGenerator`,
exactly as `BlockJsonSchemaGenerator` does). `laravel-block-schema` is left
untouched and unreferenced.

### 2. This package depends on nothing from the kernel

`laravel-timeline-schema` requires only `spatie/laravel-data`,
`laravel-package-tools`, and (for the schema projection) `laravel-data-schemas`.
It has **no** knowledge of the composition kernel — no `Stack`, no `Cell`, no
`SpineBinding`, no `Provenance`. The composition engine *depends on this package*
(like it depends on block-schema), so the kernel-facing pieces live there:

- the kernel `SpineBinding` implementation that projects a kernel `Stack` to an
  `.otio` array and hydrates it back, and
- the cell↔Clip mapping (a `Cell`'s `output` → `media_reference`; its
  `slots`/`status`/`provenance` truth → the Clip's extension `metadata`).

This package only guarantees the OTIO tree round-trips; the host stitches kernel
semantics on top.

### 3. Extension metadata rides a *configurable* namespace

OTIO `metadata` is a shared, free-form, namespaced dict (tools key by `fcp`,
`resolve`, …). The package must not hardcode an application name. The host sets
`timeline-schema.metadata_namespace` (env-driven, no default); `Support\
ExtensionMetadata` is the single place that reads it. With no namespace
configured the helper is inert (reads empty, writes throw) — `metadata` still
round-trips verbatim. The kernel's truth/output split therefore lands under the
host's namespace, never a literal baked into this package.

### 4. Emit is total; hydrate is best-effort (projection asymmetry)

`toArray()` is total and rebuildable. `OtioHydrator` resolves `OTIO_SCHEMA` via a
`SchemaRegistry` and delegates to each class's `fromOtio()`, recursing into
polymorphic children (`children`, `media_reference`). Two deliberate asymmetries:

- **Unknown schemas** (effects, transitions, vendor/NLE extensions) fall through
  to a lossless `GenericOtioObject` that stores and re-emits the raw array
  verbatim — the OTIO analogue of block-schema's `GenericNode`.
- **Unmodeled fields on known nodes** are dropped on hydrate. `metadata` survives
  (it is the extension carrier); other unmodeled OTIO fields are not. The host
  store, not the `.otio`, is the source of record — the standard is never trusted
  to round-trip the host's truth.

Empty `metadata` emits as a JSON object (`{}`) rather than a PHP-array `[]`, so
`emit → parse → emit` is byte-stable.

### 5. Validation is an optional, off-by-default seam

The default `OtioValidator` is PHP-native (`NullOtioValidator`): it asserts
serialization fidelity only and reports `semantic: false` — it never claims an
`.otio` is temporally coherent. Real schema/temporal validation (and NLE-format
interop) comes from the `opentimelineio` Python library, wired by the host as a
popcorn `RemoteInvocable` that overrides the `OtioValidator` binding. That Python
dependency and transport live in the consumer; this package ships zero Python and
pulls nothing unless explicitly configured.

## Consequences

- The package is fully isolated and independently testable (Pest + testbench);
  swapping it in/out never touches the kernel.
- Polymorphic structural fields (`children`, `media_reference`) are managed
  outside the Spatie `Data` payload, so they are absent from the per-object JSON
  Schema — the schema describes a node's own scalar/value fields, as block-schema's
  does. A recursive whole-tree schema, if ever needed, is a later addition.
- The cell↔Clip mapping, the form-track-of-Markers convention, and the
  `tempo_map`/`melody_ref` guide structures are **host conventions** expressed in
  OTIO terms; they are realized in the composition engine against this package,
  not baked into it.
