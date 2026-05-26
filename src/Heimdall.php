<?php

namespace PeterSowah\Heimdall;

class Heimdall
{
    protected static $authUsing;

    public static function auth(callable $callback): void
    {
        static::$authUsing = $callback;
    }

    public static function check($user): bool
    {
        return (static::$authUsing ?? fn ($user) => app()->environment('local'))($user);
    }
}
