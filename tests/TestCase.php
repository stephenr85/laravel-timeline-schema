<?php

namespace Rushing\TimelineSchema\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Rushing\Popcorn\Laravel\PopcornServiceProvider;
use Rushing\TimelineSchema\TimelineSchemaServiceProvider;
use Schemastud\DataSchemas\LaravelDataSchemasServiceProvider;
use Spatie\LaravelData\LaravelDataServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDataServiceProvider::class,
            LaravelDataSchemasServiceProvider::class,
            // ⚠️ Testbench does not auto-discover. Without this the RegistryIndex singleton is never
            // bound, `make()` hands back a FRESH auto-resolvable index per call, and the provider's
            // describe() writes to an object nobody reads — a green suite over an empty registry. The
            // tripwire is identity, not existence: see RegistryIndexTest.
            PopcornServiceProvider::class,
            TimelineSchemaServiceProvider::class,
        ];
    }
}
