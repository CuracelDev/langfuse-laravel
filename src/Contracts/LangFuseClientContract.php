<?php

declare(strict_types=1);

namespace Curacel\LangFuse\Contracts;

use Curacel\LangFuse\Resources\Prompt;
use Curacel\LangFuse\Resources\Tracing;
use Illuminate\Http\Client\PendingRequest;

interface LangFuseClientContract
{
    public function tracing(): Tracing;

    public function prompt(): Prompt;

    public function request(): PendingRequest;
}
