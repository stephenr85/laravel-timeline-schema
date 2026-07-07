<?php

namespace Rushing\TimelineSchema\Schema;

use LogicException;
use ReflectionClass;
use Rushing\TimelineSchema\Attributes\OtioSchema;
use Rushing\TimelineSchema\Contracts\OtioObject;
use Rushing\TimelineSchema\Contracts\SchemaRegistry;
use Rushing\TimelineSchema\Objects\Clip;
use Rushing\TimelineSchema\Objects\ExternalReference;
use Rushing\TimelineSchema\Objects\Gap;
use Rushing\TimelineSchema\Objects\Marker;
use Rushing\TimelineSchema\Objects\MissingReference;
use Rushing\TimelineSchema\Objects\Stack;
use Rushing\TimelineSchema\Objects\Timeline;
use Rushing\TimelineSchema\Objects\Track;

class OtioSchemaRegistry implements SchemaRegistry
{
    /** @var array<string, class-string<OtioObject>> */
    private array $map = [];

    public function register(string $schema, string $class): void
    {
        $this->map[$schema] = $class;
    }

    public function registerClass(string $class): void
    {
        $attrs = (new ReflectionClass($class))->getAttributes(OtioSchema::class);

        if (empty($attrs)) {
            throw new LogicException($class.' must declare an #[OtioSchema] attribute to be registered.');
        }

        $this->register($attrs[0]->newInstance()->name, $class);
    }

    public function has(string $schema): bool
    {
        return isset($this->map[$schema]);
    }

    public function resolve(string $schema): ?string
    {
        return $this->map[$schema] ?? null;
    }

    /**
     * Seed the built-in OTIO core types. Value objects (RationalTime, TimeRange)
     * are hydrated structurally by their parents, so they are not registered as
     * top-level discriminated nodes.
     */
    public function registerDefaults(): void
    {
        foreach ([
            Timeline::class,
            Stack::class,
            Track::class,
            Clip::class,
            Gap::class,
            Marker::class,
            ExternalReference::class,
            MissingReference::class,
        ] as $class) {
            $this->registerClass($class);
        }
    }
}
