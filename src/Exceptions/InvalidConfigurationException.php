<?php

namespace Malyusha\WebpackAssets\Exceptions;

use Exception;

class InvalidConfigurationException extends Exception
{
    public function __construct(array $requiredParameters = [], int $code = 0, \Throwable $previous = null)
    {
        parent::__construct(sprintf('Invalid configuration. Parameters required %s', implode(', ', $requiredParameters)), $code, $previous);
    }
}