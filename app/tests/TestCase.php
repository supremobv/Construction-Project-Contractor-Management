<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.$connection.database");
        if ($connection !== 'sqlite' && (! is_string($database) || ! str_ends_with($database,'_test'))) {
            throw new \RuntimeException('Tests refused: use an isolated database ending in _test. Never run tests on your working database.');
        }
        return $app;
    }
}
