<?php

namespace Arris\Toolkit\CLI\Exceptions;

use RuntimeException;

final class MissingRequiredOptionException extends RuntimeException
{
    public function __construct(
        public readonly string $optionName,
        public readonly ?string $shortName,
    ) {
        parent::__construct(
            "Missing required value for option --{$optionName}" .
            ($shortName !== null ? " (-{$shortName})" : '')
        );
    }
}