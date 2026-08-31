<?php

namespace Rushing\TimelineSchema;

use Rushing\TimelineSchema\Contracts\OtioValidator;
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
        //
        // ⚠️ Bound on the CONCRETE class. It used to bind a package-local `Contracts\SchemaRegistry`
        // interface that re-declared the kernel contract with narrower types — registry-kernel ticket
        // 34 D3/D4 deleted it: nothing outside this package type-hinted it, and an `#[IsRegistry]` can
        // never sit on an interface (the attribute walk is the class parent chain), so a row pointed at
        // it was a row no audit could ever see satisfied.
        $this->app->singleton(OtioSchemaRegistry::class, fn (): OtioSchemaRegistry => (new OtioSchemaRegistry)->registerDefaults());

        $this->app->bind(
            OtioHydrator::class,
            fn ($app) => new OtioHydrator($app->make(OtioSchemaRegistry::class)),
        );

        // PHP-native validation is the default: serialization fidelity only, no
        // semantic/temporal checks. A host wires a popcorn-backed RemoteInvocable
        // over the `opentimelineio` Python lib to override this when configured.
        $this->app->singleton(OtioValidator::class, NullOtioValidator::class);
    }

    public function packageBooted(): void {}
}
