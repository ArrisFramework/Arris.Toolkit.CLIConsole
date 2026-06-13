<?php

namespace Arris\Toolkit;

use Closure;

final readonly class CLIOption
{
    public readonly bool $required;

    public function __construct(
        public string $name,
        public ?string $short,
        public string $description,
        public CLIOptionType $type,
        public mixed $default,
        ?bool $required = null,
        public Closure|array|null $handler = null,
    ) {
        // Автоматически делаем required, если тип WithValue и нет default
        $this->required = $required ?? ($this->type === CLIOptionType::WithValue && $this->default === null);
    }

    public function isFlag(): bool
    {
        return $this->type === CLIOptionType::Flag;
    }

    public function getLongPattern(): string
    {
        return $this->name . match ($this->type) {
                CLIOptionType::Flag => '',
                CLIOptionType::WithValue => ':',
                CLIOptionType::WithOptionalValue => '::',
            };
    }

    public function getShortPattern(): string
    {
        if ($this->short === null) {
            return '';
        }
        return $this->short . match ($this->type) {
                CLIOptionType::Flag => '',
                CLIOptionType::WithValue => ':',
                CLIOptionType::WithOptionalValue => '::',
            };
    }
}
