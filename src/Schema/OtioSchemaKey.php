<?php

namespace Rushing\TimelineSchema\Schema;

use Rushing\Popcorn\Registries\RegistryKey;
use Rushing\TimelineSchema\Exceptions\InvalidOtioSchemaLabel;

/**
 * An `OTIO_SCHEMA` label — `Timeline.1`, `Clip.2`, `ExternalReference.1` — as a registry key: **one
 * opaque segment**, the whole label, compared whole.
 *
 * ## Why this is a consumer-owned key rather than a {@see \Rushing\Popcorn\Registries\Key}
 *
 * Registry-kernel [ticket 58 D5](../../../../../splicewire-ecosystem/.scratch/rushing/laravel-popcorn/registry-kernel/tickets/58-the-keyspaces-key-cannot-express.md)
 * dispositioned this registry as *"the one genuine foreign-key row"* in the estate, and the reason is
 * that **we do not own the spelling**. `Timeline.1` is OpenTimelineIO's vocabulary: it is uppercase,
 * and its `.` is OTIO's own name/version joiner, not an address separator. The kernel keyspace is
 * dot-separated **lowercase** segments, so `Key::of('Timeline.1')` throws twice over — once on the
 * charset and once on the fact that splitting at the dot would manufacture a two-level address OTIO
 * never meant.
 *
 * Ticket 58 D1's convention is *references are typed, addresses are resolved* — a registry that
 * accepts a foreign reference translates it at its door and stores an address. The three registries
 * that already do that (`ConduitCapabilityRegistry::addressOf()`, `CapabilityLadder::resolveReference()`,
 * `SchemaOrgRegistry::keyFor()`) all translate a reference **we mint** into an address we own. This one
 * cannot: the label ships in and out of `.otio` JSON as the `OTIO_SCHEMA` discriminator, so a
 * translation would have to be lossless in both directions and would buy nothing, because there is no
 * second spelling anyone uses. Keeping the label whole is the honest form.
 *
 * ## It is NOT {@see \Rushing\Popcorn\Registries\Rootable}, deliberately
 *
 * The mirror of {@see \Rushing\Popcorn\Registries\AbsoluteUriKey}, which implements `Rootable` and
 * declines. Here the type simply does not participate: `BasicRegistry::door()` type-tests `Rootable`
 * and passes anything else through unchanged, so an unstamped key is already the door's own defined
 * behaviour and an `underRoot()` returning `$this` would restate it.
 *
 * The consequence is the one `AbsoluteUriKey`'s docblock names and it is accepted knowingly: entries
 * keyed this way are **not addressable through the global keyspace** — `matches(root)` over the index
 * does not reach them, and they are reachable as a registry through the index, never through `pop()`.
 * That is the price of a vocabulary somebody else owns, and it is what "relative-forever" means.
 *
 * ## Equality is on segments, never on the source string
 *
 * The kernel's rule ({@see RegistryKey}). Here there is one segment and it is the label verbatim, so
 * equality is label equality — **case-sensitively**, because OTIO's labels are case-sensitive and
 * folding them would merge `Clip.2` with a hypothetical `clip.2` that OTIO would read as a different
 * schema.
 */
class OtioSchemaKey implements RegistryKey
{
    /**
     * An OTIO schema label: a name followed by `.` and an integer version.
     *
     * Matched to REQUIRE. This is grammar the caller could have gotten right without knowing which host
     * loaded the class, so it is a legitimate throw rather than an advisory finding (the ecosystem
     * `AGENTS.md` rule). `\z` rather than `$`, per the kernel's own `Key::SEGMENT_PATTERN`.
     */
    public const PATTERN = '/^[A-Za-z][A-Za-z0-9_]*\.\d+\z/';

    private function __construct(private string $label) {}

    /** @throws InvalidOtioSchemaLabel */
    public static function of(RegistryKey|string $label): RegistryKey
    {
        if ($label instanceof RegistryKey) {
            return $label;
        }

        if (preg_match(self::PATTERN, $label) !== 1) {
            throw new InvalidOtioSchemaLabel($label);
        }

        return new self($label);
    }

    /** @return list<string> */
    public function segments(): array
    {
        return [$this->label];
    }

    public function equals(RegistryKey $other): bool
    {
        return $this->segments() === $other->segments();
    }

    /** The label itself, verbatim — this is also what ships in `.otio` JSON. */
    public function __toString(): string
    {
        return $this->label;
    }
}
