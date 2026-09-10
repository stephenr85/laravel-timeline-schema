# Clip media references

Clip serializes the native `Clip.2` shape: `media_references` is a keyed map and `active_media_reference_key` selects its active member. `mediaReference()` and `withMediaReference()` read and replace that member while preserving inactive references. `withMediaReferences()` replaces the map and validates its active key.

The hydrator also reads the singular `media_reference` field emitted by earlier versions of this package. Its next serialization upgrades that field to canonical Clip.2. Consumers inspecting arrays must read the selected map member; a singular-field fallback permits their existing stored documents to remain readable. This compatibility upgrade is not a byte-identical round trip of the old, invalid Clip.2 representation.

The contract follows OTIO's [serialized schema](https://opentimelineio.readthedocs.io/en/v0.15/tutorials/otio-serialized-schema.html). Verified with native OpenTimelineIO 0.18.0: a typed PHP audio timeline parses with its two-second duration and media target intact. PHP-only self-round trips did not catch the earlier incompatible field spelling.

The native multiple-reference fixture tests active selection, preservation of inactive media during edits, and rejection of an absent active key. Generate it from the package root with Python containing `opentimelineio==0.18.0`:

```sh
python - <<'PY'
import opentimelineio as otio
clip = otio.schema.Clip(name='native multi-reference control')
clip.set_media_references({
    'camera': otio.schema.ExternalReference(target_url='file:///camera.mov'),
    'proxy': otio.schema.ExternalReference(target_url='file:///proxy.mov'),
}, 'proxy')
otio.adapters.write_to_file(clip, 'tests/fixtures/native-clip-v2.otio')
PY
```

Tests read the fixture; they never regenerate it. This reference test does not certify preservation of every OTIO field or effect.
