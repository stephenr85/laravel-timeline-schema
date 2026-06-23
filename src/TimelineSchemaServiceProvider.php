<?php

declare(strict_types=1);

namespace Rushing\TimelineSchema;

use Rushing\TimelineSchema\Contracts\OtioValidator;
use Rushing\TimelineSchema\Contracts\SchemaRegistry;
use Rushing\TimelineSchema\Schema\OtioSchemaRegistry;
use Rushing\TimelineSchema\Validation\NullOtioValidator;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TimelineSchemaServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-timeline-schema')
            ->hasConfigFile('timeline-schema');
    }

    public function registeringPackage(): void
    {
        // The OTIO_SCHEMA registry, pre-seeded with the built-in core types.
        $this->app->singleton(SchemaRegistry::class, function (): SchemaRegistry {
            $registry = new OtioSchemaRegistry;
            $registry->registerDefaults();

            return $registry;
        });

        $this->app->bind(
            OtioHydrator::class,
            fn ($app) => new OtioHydrator($app->make(SchemaRegistry::class)),
        );

        // PHP-native validation is the default: serialization fidelity only, no
        // semantic/temporal checks. A host wires a popcorn-backed RemoteInvocable
        // over the `opentimelineio` Python lib to override this when configured.
        $this->app->singleton(OtioValidator::class, NullOtioValidator::class);
    }
}
