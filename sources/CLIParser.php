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
     * @return array{values: array<string, mixed>, help: bool, verbose: bool}
     * @throws CLIMissingRequiredOptionException
     */
    public function parse(array $argv): array
    {
        $short = 'hv';
        $long = ['help', 'verbose'];

        foreach ($this->options as $option) {
            if ($option->name === 'verbose' || $option->short === 'v') {
                throw new RuntimeException(
                    "Option name 'verbose' or short 'v' is reserved for built-in verbose mode"
                );
            }

            $short .= $option->getShortPattern();
            $long[] = $option->getLongPattern();
        }

        $parsed = getopt($short, $long);

        if ($parsed === false) {
            throw new RuntimeException('Failed to parse CLI options');
        }

        $help = array_key_exists('h', $parsed) || array_key_exists('help', $parsed);
        $verbose = array_key_exists('v', $parsed) || array_key_exists('verbose', $parsed);

        $values = [];
        foreach ($this->options as $name => $option) {
            if (array_key_exists($name, $parsed)) {
                $values[$name] = $option->isFlag() ? true : $parsed[$name];
            } elseif ($option->short !== null && array_key_exists($option->short, $parsed)) {
                $values[$name] = $option->isFlag() ? true : $parsed[$option->short];
            } elseif ($option->default !== null) {
                $values[$name] = $option->default;
            }
        }

        if (!$help) {
            foreach ($this->options as $option) {
                if (!$option->required) {
                    continue;
                }

                if (!array_key_exists($option->name, $values)) {
                    throw new CLIMissingRequiredOptionException($option->name, $option->short);
                }
            }
        }

        return [
            'values' => $values,
            'help' => $help,
            'verbose' => $verbose,
        ];
    }

    /** @return array<string, CLIOption> */
    public function getOptions(): array
    {
        return $this->options;
    }
}
