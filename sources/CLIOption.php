<?php

namespace Arris\Toolkit;

use Closure;

final readonly class CLIOption
{
    public function __construct(
        public string $name,
        public ?string $short,
        public string $description,
        public CLIOptionType $type,
        public mixed $default,
        public Closure|array|null $handler,
    ) {}

    public function isFlag(): bool
    {
        return $this->type === CLIOptionType::Flag;
    }

    /** Паттерн для длинной опции в getopt (name | name: | name::) */
    public function getLongPattern(): string
    {
        return $this->name . match ($this->type) {
                CLIOptionType::Flag => '',
                CLIOptionType::RequiredValue => ':',
                CLIOptionType::OptionalValue => '::',
            };
    }

    /** Паттерн для короткой опции в getopt (r | r: | r::) */
    public function getShortPattern(): string
    {
        if ($this->short === null) {
            return '';
        }
        return $this->short . match ($this->type) {
                CLIOptionType::Flag => '',
                CLIOptionType::RequiredValue => ':',
                CLIOptionType::OptionalValue => '::',
            };
    }
}