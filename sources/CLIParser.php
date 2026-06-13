<?php

namespace Arris\Toolkit;

use RuntimeException;

/**
 * Парсер CLI-опций: регистрация + парсинг argv
 */
final class CLIParser
{
    /** @var array<string, CLIOption> */
    private array $options = [];

    public function add(CLIOption $option): self
    {
        if (isset($this->options[$option->name])) {
            throw new RuntimeException("Option '{$option->name}' already registered");
        }
        $this->options[$option->name] = $option;
        return $this;
    }

    /**
     * Парсит argv и возвращает распарсенные значения + признак запроса help
     *
     * @param array<int, string> $argv
     * @return array{values: array<string, mixed>, help: bool}
     */
    public function parse(array $argv): array
    {
        $short = 'h';
        $long = ['help'];

        foreach ($this->options as $option) {
            $short .= $option->getShortPattern();
            $long[] = $option->getLongPattern();
        }

        $parsed = getopt($short, $long);

        if ($parsed === false) {
            throw new RuntimeException('Failed to parse CLI options');
        }

        $values = [];
        foreach ($this->options as $name => $option) {
            // Приоритет: длинная опция > короткая > default
            if (array_key_exists($name, $parsed)) {
                $values[$name] = $option->isFlag() ? true : $parsed[$name];
            } elseif ($option->short !== null && array_key_exists($option->short, $parsed)) {
                $values[$name] = $option->isFlag() ? true : $parsed[$option->short];
            } elseif ($option->default !== null) {
                $values[$name] = $option->default;
            }
        }

        return [
            'values' => $values,
            'help' => array_key_exists('h', $parsed) || array_key_exists('help', $parsed),
        ];
    }

    /** @return array<string, CLIOption> */
    public function getOptions(): array
    {
        return $this->options;
    }
}
