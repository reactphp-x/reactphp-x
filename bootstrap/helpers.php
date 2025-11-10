<?php

use ReactphpX\CycleDatabase\AsyncDatabase;
use Cycle\Database\Table;

if (!function_exists('config_path')) {
    function config_path(string $path = ''): string
    {
        $base = base_path('config');
        return $path ? $base . '/' . ltrim($path, '/') : $base;
    }
}

if (!function_exists('config')) {
    function config(string $key, $default = null)
    {
        static $configs = null;
        if ($configs === null) {
            $configs = [];
            foreach (glob(config_path('*.php')) ?: [] as $file) {
                $name = basename($file, '.php');
                $configs[$name] = require $file;
            }
        }
        $segments = explode('.', $key);
        $value = $configs;
        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }
        return $value;
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = realpath(__DIR__ . '/..');
        return $path ? $base . '/' . ltrim($path, '/') : $base;
    }
}

if (!function_exists('app')) {
    function app(?string $abstract = null)
    {
        $container = $GLOBALS['__container'] ?? null;
        if ($container === null) {
            throw new \RuntimeException('Container not initialized. Make sure to include bootstrap/app.php');
        }
        if ($abstract === null) {
            return $container;
        }
        return $container->get($abstract);
    }
}

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}

if (!function_exists('db')) {
    function db(string $database = 'default'): AsyncDatabase
    {
        return app('db')->database($database);
    }
}

if (!function_exists('table')) {
    function table(string $table, string $database = 'default'): Table
    {
        return app('db')->database($database)->table($table);
    }
}


