<?php

declare(strict_types=1);

namespace Curacel\LangFuse\Exceptions;

final class ConfigurationException extends \Exception
{
    public static function noSecretKey(): ConfigurationException
    {
        return new ConfigurationException(
            message: 'Secret Key is missing. Ensure this is set.',
        );
    }

    public static function noPublicKey(): ConfigurationException
    {
        return new ConfigurationException(
            message: 'Public Key is missing. Ensure this is set.',
        );
    }
}
