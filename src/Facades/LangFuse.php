<?php

namespace Curacel\LangFuse\Facades;

use Curacel\LangFuse\Resources\Prompt;
use Curacel\LangFuse\Resources\Tracing;
use Illuminate\Support\Facades\Facade;

final class LangFuse extends Facade
{
    public static function tracing(): Tracing
    {
        return LangFuse::getFacadeRoot()->tracing();
    }

    public static function prompt(): Prompt
    {
        return LangFuse::getFacadeRoot()->prompt();
    }

    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'langfuse';
    }
}
