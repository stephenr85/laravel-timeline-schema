<?php

namespace Rushing\TimelineSchema\Contracts;

/**
 * Maps an `OTIO_SCHEMA` label to the class that models it. The app may register
 * its own extension object types; the hydrator reads it. Unknown labels fall
 * through to a lossless passthrough rather than failing.
 */
interface SchemaRegistry
{
    /**
     * @param  class-string<OtioObject>  $class
     */
    public function register(string $schema, string $class): void;

    /**
     * Register a class by reading its #[OtioSchema] attribute label.
     *
     * @param  class-string<OtioObject>  $class
     */
    public function registerClass(string $class): void;

    public function has(string $schema): bool;

    /**
     * @return class-string<OtioObject>|null
     */
    public function resolve(string $schema): ?string;
}
