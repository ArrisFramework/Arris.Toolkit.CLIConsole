<?php

namespace Arris\Toolkit;

use RuntimeException;

final class CLIMissingRequiredOptionException extends RuntimeException
{
    public function __construct(
        public readonly string $optionName,
        public readonly ?string $shortName,
    ) {
        parent::__construct(
            "Missing required option: --{$optionName}" .
            ($shortName !== null ? " (-{$shortName})" : '')
        );
    }
}