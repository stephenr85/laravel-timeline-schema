<?php

namespace Rushing\TimelineSchema\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Schemastud\DataSchemas\LaravelDataSchemasServiceProvider;
use Rushing\TimelineSchema\TimelineSchemaServiceProvider;
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
            TimelineSchemaServiceProvider::class,
        ];
    }
}
