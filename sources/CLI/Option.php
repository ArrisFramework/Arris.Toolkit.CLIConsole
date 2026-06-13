<?php

namespace Arris\Toolkit\CLI;

use Closure;

final readonly class Option
{
    public readonly bool $required;

    public function __construct(
        public string             $name,
        public ?string            $short,
        public string             $description,
        public OptionType         $type,
        public mixed              $default,
        ?bool                     $required = null,
        public Closure|array|null $handler = null,
    ) {
        // Автоматически делаем required, если тип WithValue и нет default
        $this->required = $required ?? ($this->type === OptionType::WithValue && $this->default === null);
    }

    public function isFlag(): bool
    {
        return $this->type === OptionType::Flag;
    }

    public function getLongPattern(): string
    {
        return $this->name . match ($this->type) {
                OptionType::Flag => '',
                OptionType::WithValue => ':',
                OptionType::WithOptionalValue => '::',
            };
    }

    public function getShortPattern(): string
    {
        if ($this->short === null) {
            return '';
        }
        return $this->short . match ($this->type) {
                OptionType::Flag => '',
                OptionType::WithValue => ':',
                OptionType::WithOptionalValue => '::',
            };
    }
}
