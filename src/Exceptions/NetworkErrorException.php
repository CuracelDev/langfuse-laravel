<?php

namespace Curacel\LangFuse\Exceptions;

final class NetworkErrorException extends \Exception
{
    public function __construct(public $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Check if this error might be transient (worth retrying)
     */
    public function isRetryable(): bool
    {
        $message = strtolower($this->getMessage());

        // Common transient network issues
        $transientPatterns = [
            'timeout',
            'connection reset',
            'connection refused',
            'temporary failure',
            'could not resolve host',
            'network unreachable',
        ];

        foreach ($transientPatterns as $pattern) {
            if (str_contains($message, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
