<?php

declare(strict_types=1);

namespace Tests\Support;

final class ConfigCache
{
    public static function reset(): void
    {
        $ref = new \ReflectionFunction('config');

        if (method_exists($ref, 'setStaticVariable')) {
            $ref->setStaticVariable('configs', null);
        }
    }
}
