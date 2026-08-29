<?php

namespace Rushing\TimelineSchema\Schema;

use LogicException;
use ReflectionClass;
use Rushing\Popcorn\Registries\BasicRegistry;
use Rushing\Popcorn\Registries\IsRegistry;
use Rushing\Popcorn\Registries\OnDuplicate;
use Rushing\Popcorn\Registries\Optionality;
use Rushing\Popcorn\Registries\Registry;
use Rushing\Popcorn\Registries\RegistryArity;
use Rushing\Popcorn\Registries\RegistryKey;
use Rushing\TimelineSchema\Attributes\OtioSchema;
use Rushing\TimelineSchema\Contracts\OtioObject;
use Rushing\TimelineSchema\Objects\Clip;
use Rushing\TimelineSchema\Objects\ExternalReference;
use Rushing\TimelineSchema\Objects\Gap;
use Rushing\TimelineSchema\Objects\Marker;
use Rushing\TimelineSchema\Objects\MissingReference;
use Rushing\TimelineSchema\Objects\Stack;
use Rushing\TimelineSchema\Objects\Timeline;
use Rushing\TimelineSchema\Objects\Track;

/**
 * Maps an `OTIO_SCHEMA` label to the class that models it. The app may register its own extension
 * object types; {@see \Rushing\TimelineSchema\OtioHydrator} reads it. Unknown labels fall through to a
 * lossless passthrough rather than failing.
 *
 * ## Declared, as of registry-kernel's outstanding burn-down
 *
 * Registry-kernel [ticket 34 D3/D4](../../../../../splicewire-ecosystem/.scratch/rushing/laravel-popcorn/registry-kernel/tickets/34-reprice-the-private-registry-bar.md)
 * ruled two things about this row that are both landed here:
 *
 *   - the ledger row was pointed at `Rushing\TimelineSchema\Contracts\SchemaRegistry`, **an
 *     `interface`** — and the `#[IsRegistry]` walk is the class parent chain only, so a declaration
 *     there would have governed nothing while both audits carried on not seeing the real class. The
 *     row is re-pointed here, and the provider now binds this concrete class;
 *   - that local `Contracts\SchemaRegistry` **duplicated** the kernel contract with narrower types and
 *     was type-hinted nowhere outside this package, so D4's discriminator (*"does anything outside the
 *     owning package type-hint it?"* — no) says **delete**, not `extends Registry`. It is deleted.
 *
 * ## ⚠️ The keys are a foreign vocabulary, which is the whole reason this row was hard
 *
 * `Timeline.1` is OpenTimelineIO's spelling, not ours: uppercase, and its `.` joins a name to a
 * version rather than addressing a branch. [Ticket 58 D5](../../../../../splicewire-ecosystem/.scratch/rushing/laravel-popcorn/registry-kernel/tickets/58-the-keyspaces-key-cannot-express.md)
 * dispositioned it as *"the one genuine foreign-key row"* in the estate — it keeps a consumer-owned
 * {@see OtioSchemaKey}, relative-forever, deliberately. The accepted consequence is that entries here
 * are not addressable through the global keyspace; see that class's docblock.
 *
 * ## ⚠️ `resolve()` changed meaning; the old meaning was renamed to {@see classFor()}
 *
 * The same collision `NavKindRegistry` hit, for the same reason. Both answer *"what is under this
 * key"*, but they disagree on the miss: the kernel's {@see Registry::resolve()} THROWS, while this
 * class's returned `null` — and that null is load-bearing, because `OtioHydrator` reads it as
 * *fall back to the lossless passthrough*. A signature that still type-checks while meaning something
 * else fails silently; a rename fails at every call site. `classFor()` also keeps the nullable INPUT,
 * which the kernel's key contract does not admit.
 *
 * `has()` is likewise NOT a bare delegation: it is asked about labels read straight out of `.otio`
 * JSON, so a malformed one must answer `false` rather than throw {@see \Rushing\TimelineSchema\Exceptions\InvalidOtioSchemaLabel}
 * out of a validation call (ticket 58's second-order finding).
 *
 * @implements Registry<class-string<OtioObject>>
 */
#[IsRegistry(
    root: 'otio.schemas',
    of: 'OTIO object types — one concrete OtioObject class per OTIO_SCHEMA label, so a document hydrates into typed nodes',
    arity: RegistryArity::PickOne,
    entryType: OtioObject::class,
    onDuplicate: OnDuplicate::Supersede,
    optionality: Optionality::Optional,
    note: 'Keys are OpenTimelineIO labels (`Timeline.1`), held as consumer-owned OtioSchemaKeys and '
        .'never root-stamped, so this branch is reachable through the index and not through pop() '
        .'(registry-kernel 58 D5). Supersede records the behaviour this class always had — registration '
        .'was a plain array assignment, and a host overriding a built-in OTIO type with its own subclass '
        .'is a supported act.',
)]
class OtioSchemaRegistry implements Registry
{
    /**
     * The OTIO core types this package ships. Value objects (`RationalTime`, `TimeRange`) are hydrated
     * structurally by their parents, so they are not registered as top-level discriminated nodes.
     *
     * @var list<class-string<OtioObject>>
     */
    public const BUILT_INS = [
        Timeline::class,
        Stack::class,
        Track::class,
        Clip::class,
        Gap::class,
        Marker::class,
        ExternalReference::class,
        MissingReference::class,
    ];

    /** @var BasicRegistry<class-string<OtioObject>> */
    private BasicRegistry $schemas;

    public function __construct()
    {
        $this->schemas = BasicRegistry::for($this);
    }

    /**
     * Register (or override) a concrete object class for an `OTIO_SCHEMA` label.
     *
     * The kernel's signature. Source-compatible with every existing two-argument call — the entry
     * widens to `mixed` and the return type widens from `void` to `static`.
     *
     * @param  class-string<OtioObject>  $entry
     */
    public function register(RegistryKey|string $key, mixed $entry = null, ?string $by = null, ?string $ability = null): static
    {
        $this->schemas->register(OtioSchemaKey::of($key), $entry, $by, $ability);

        return $this;
    }

    /**
     * Register a class by reading the label off its `#[OtioSchema]` attribute.
     *
     * @param  class-string<OtioObject>  $class
     */
    public function registerClass(string $class): static
    {
        $attrs = (new ReflectionClass($class))->getAttributes(OtioSchema::class);

        if ($attrs === []) {
            throw new LogicException($class.' must declare an #[OtioSchema] attribute to be registered.');
        }

        return $this->register($attrs[0]->newInstance()->name, $class, by: self::class);
    }

    /** Seed the built-in OTIO core types — see {@see BUILT_INS}. */
    public function registerDefaults(): static
    {
        foreach (self::BUILT_INS as $class) {
            $this->registerClass($class);
        }

        return $this;
    }

    /**
     * Resolve an `OTIO_SCHEMA` label to its concrete object class, or null when unregistered — the
     * hydrator then falls back to its lossless passthrough, as before.
     *
     * This is what {@see resolve()} used to be, and the null return is the contract `OtioHydrator`
     * depends on. A null `$schema` is admitted for the same reason: the hydrator is handed whatever the
     * document had, including nothing. A **malformed** label is a miss here rather than a throw, for
     * the same reason again — it arrived on the wire, not from a declaration.
     *
     * @return class-string<OtioObject>|null
     */
    public function classFor(?string $schema): ?string
    {
        if ($schema === null || preg_match(OtioSchemaKey::PATTERN, $schema) !== 1) {
            return null;
        }

        return $this->schemas->tryResolve(OtioSchemaKey::of($schema));
    }

    /**
     * Every registered label, keyed by the `OTIO_SCHEMA` label **as it ships in `.otio` JSON**.
     *
     * Safe to render directly here — unlike a root-stamped key, an {@see OtioSchemaKey} is never
     * stamped, so `(string) $key` is the label verbatim. That is the property the foreign-key
     * disposition buys.
     *
     * @return array<string, class-string<OtioObject>>
     */
    public function all(): array
    {
        $schemas = [];

        foreach ($this->schemas->keys() as $key) {
            $schemas[(string) $key] = $this->schemas->resolve($key);
        }

        return $schemas;
    }

    /* ---------------- Registry contract ---------------- */

    /**
     * ⚠️ Answers `false` for a malformed label rather than throwing — it is asked about labels read
     * straight out of a document. See the class docblock.
     */
    public function has(RegistryKey|string $key): bool
    {
        if (is_string($key) && preg_match(OtioSchemaKey::PATTERN, $key) !== 1) {
            return false;
        }

        return $this->schemas->has(OtioSchemaKey::of($key));
    }

    /** ⚠️ THROWS on a miss, unlike {@see classFor()}. See the class docblock. */
    public function resolve(RegistryKey|string $key): mixed
    {
        return $this->schemas->resolve(OtioSchemaKey::of($key));
    }

    public function tryResolve(RegistryKey|string $key): mixed
    {
        return $this->schemas->tryResolve(OtioSchemaKey::of($key));
    }

    /** @return list<class-string<OtioObject>> */
    public function matches(RegistryKey|string $key): array
    {
        return $this->schemas->matches(OtioSchemaKey::of($key));
    }

    /** @return list<RegistryKey> */
    public function keys(): array
    {
        return $this->schemas->keys();
    }

    public function unfiltered(): Registry
    {
        return $this->schemas->unfiltered();
    }
}
