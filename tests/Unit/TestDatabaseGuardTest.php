<?php

use Tests\TestCase;

it('allows in-memory sqlite', function () {
    TestCase::assertSafeTestDatabase('sqlite', ':memory:');
    expect(true)->toBeTrue();
});

it('allows a database whose name ends in _test', function (string $driver, string $database) {
    TestCase::assertSafeTestDatabase($driver, $database);
    expect(true)->toBeTrue();
})->with([
    ['mysql', 'dashboard_main_test'],
    ['sqlite', 'database/dashboard_test.sqlite'],
]);

it('refuses any other database', function (string $driver, ?string $database) {
    TestCase::assertSafeTestDatabase($driver, $database);
})->with([
    ['mysql', 'dashboard_main'],
    ['mysql', 'test_dashboard'],
    ['mysql', null],
    ['sqlite', 'database/database.sqlite'],
])->throws(RuntimeException::class);
