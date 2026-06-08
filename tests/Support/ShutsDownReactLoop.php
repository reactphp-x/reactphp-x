<?php

declare(strict_types=1);

namespace Tests\Support;

use React\EventLoop\Loop;
use ReactphpX\CycleDatabase\AsyncDatabaseManager;

final class ShutsDownReactLoop
{
    public static function closeDatabase(): void
    {
        if (! isset($GLOBALS['__container'])) {
            return;
        }

        try {
            $db = app('db');
            if ($db instanceof AsyncDatabaseManager) {
                foreach ($db->getDrivers() as $driver) {
                    self::shutdownDriver($driver);
                }
            }
        } catch (\Throwable) {
            // ignore teardown errors
        }

        unset($GLOBALS['__container']);
    }

    public static function shutdown(): void
    {
        self::closeDatabase();
        Loop::stop();
    }

    private static function shutdownDriver(object $driver): void
    {
        if (method_exists($driver, 'quit')) {
            try {
                $driver->quit();
            } catch (\Throwable) {
                // fall through to close
            }
        }

        if (method_exists($driver, 'close')) {
            try {
                $driver->close();
            } catch (\Throwable) {
            }
        }
    }
}
