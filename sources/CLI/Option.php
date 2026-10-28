<?php

namespace Arris\Toolkit\CLI;

use Closure;

final readonly class Option
{
    public readonly bool $required;

    /**
     * @param string $name
     * @param string|null $short
     * @param string $description
     * @param OptionType $type
     * @param mixed $default
     * @param bool|null $required
     * @param Closure|array|null $handler
     * @param string|null $group
     */
    public function __construct(
        public string             $name,
        public ?string            $short,
        public string             $description,
        public OptionType         $type,
        public mixed              $default,
        ?bool                     $required = null,
        public Closure|array|null $handler = null,
        public ?string            $group = null,
    ) {
        $this->required = $required ?? false;
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
