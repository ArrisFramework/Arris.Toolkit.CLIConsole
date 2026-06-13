<?php

namespace Arris\Toolkit;

use Arris\Toolkit\CLI\OptionType;

interface CLIRunnerInterface
{
    public function __construct(?string $scriptDescription = null);
    public function registerClass(string $className, object $instance): self;

    public function register(
        string             $option,
        ?string            $short = null,
        string             $description = '',
        OptionType         $type = OptionType::Flag,
        mixed              $default = null,
        ?bool              $required = null,
        Closure|array|null $handler = null,
    ): self;

    public function has(string $option): bool;
    public function get(string $option): mixed;
    public function set(string $key, mixed $value): void;
    public function getVar(string $key): mixed;
    public function isVerbose(): bool;
    public function say(string $message, bool $always = false): void;

    public function run(): bool;

}