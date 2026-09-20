<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Refuse to run against a database that is a real file.
     *
     * `phpunit.xml` asks for `:memory:`, but passing an explicit testing
     * environment on the command line loads `.env.testing`, whose
     * DB_DATABASE is `database/testing.sqlite` — and that wins, `force` on
     * the phpunit env or not. That file is the one the Playwright server
     * reads and writes, and two separate things then go wrong:
     *
     *  - RefreshDatabase empties the site the browser suite is testing.
     *    There is then no admin to log in as, so every admin spec fails on
     *    the login page, which looks like an authentication bug and is not.
     *  - Both processes hold one SQLite file on the default rollback journal.
     *    One run produced 130 "database is locked" failures from this alone.
     *
     * Neither failure names its cause, and both cost hours to trace.
     *
     * This hangs off `refreshApplication` rather than `setUp` because it has
     * to run in the one window where the check is possible and still useful:
     * after the container exists, so there is a config to read, and before
     * `setUpTraits` runs RefreshDatabase, so nothing has been dropped yet.
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $database = config('database.connections.'.config('database.default').'.database');

        if ($database !== ':memory:' && ! str_contains((string) $database, ':memory:')) {
            throw new RuntimeException(
                'Refusing to run: the tests are pointed at "'.$database.'", not an in-memory database. '
                .'This is almost always `--env=testing` on the command line, which loads .env.testing and '
                .'takes over from phpunit.xml. Drop the flag: the suite already runs in the testing '
                .'environment, and the file it would otherwise wipe is the one the browser suite uses.'
            );
        }
    }
}
