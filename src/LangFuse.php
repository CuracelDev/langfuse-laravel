<?php

namespace Curacel\LangFuse;

final class LangFuse
{
    public const VERSION = '0.0.1';

    public static array $failureCallbacks = [];

    public static function onErrorCallback(?\Closure $callback = null): void
    {
        LangFuse::$failureCallbacks[] = $callback ?: function () {};
    }
}
