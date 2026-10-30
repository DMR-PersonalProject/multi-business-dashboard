<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Boot the app and refuse to continue unless the database is safe to wipe.
     * This runs before RefreshDatabase migrates anything.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $connection = $app['config']->get('database.default');
        $driver = $app['config']->get("database.connections.{$connection}.driver");
        $database = $app['config']->get("database.connections.{$connection}.database");

        static::assertSafeTestDatabase((string) $driver, $database === null ? null : (string) $database);

        // Tests never need the real key; a random one keeps cookies and encryption working without .env.
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        return $app;
    }

    /**
     * Allow only in-memory SQLite or a database whose name ends in "_test".
     */
    public static function assertSafeTestDatabase(string $driver, ?string $database): void
    {
        if ($driver === 'sqlite' && $database === ':memory:') {
            return;
        }

        $name = pathinfo((string) $database, PATHINFO_FILENAME);

        if ($name !== '' && str_ends_with($name, '_test')) {
            return;
        }

        throw new RuntimeException(
            'Refusing to run tests: they reset the database. Use in-memory SQLite (see phpunit.xml) '
            .'or a database whose name ends in "_test".'
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Views use @vite; tests don't need built assets.
        $this->withoutVite();
    }
}
