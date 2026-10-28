<?php

namespace Arris\Toolkit\CLI;

use Arris\Toolkit\CLI\Exceptions\MissingRequiredOptionException;
use RuntimeException;

/**
 * Парсер CLI-опций на основе getopt() (legacy)
 */
final class ParserNative
{
    /** @var array<string, Option> */
    private array $options = [];

    public function add(Option $option): self
    {
        if (isset($this->options[$option->name])) {
            throw new RuntimeException("Option '{$option->name}' already registered");
        }
        $this->options[$option->name] = $option;
        return $this;
    }

    /**
     * @return array{values: array<string, mixed>, help: bool, verbose: bool}
     * @throws MissingRequiredOptionException
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

        $this->detectMissingValueOnGetoptFailure($argv);

        $parsed = @getopt($short, $long);

        if ($parsed === false) {
            throw new RuntimeException('Failed to parse CLI options');
        }

        $help = array_key_exists('h', $parsed) || array_key_exists('help', $parsed);
        $verbose = array_key_exists('v', $parsed) || array_key_exists('verbose', $parsed);

        $values = [];
        foreach ($this->options as $name => $option) {
            if (array_key_exists($name, $parsed)) {
                if ($option->isFlag()) {
                    $values[$name] = true;
                } else {
                    $rawValue = $parsed[$name];
                    if ($rawValue === false) {
                        if ($option->default !== null) {
                            $values[$name] = $option->default;
                            continue;
                        }
                        $shortLabel = $option->short ? " (-{$option->short})" : '';
                        throw new MissingRequiredOptionException(
                            $option->name,
                            $option->short,
                            "value for option --{$option->name}{$shortLabel} REQUIRED, default value is not set"
                        );
                    }
                    if ($rawValue === '') {
                        if ($option->default !== null) {
                            $values[$name] = $option->default;
                            continue;
                        }
                        $shortLabel = $option->short ? " (-{$option->short})" : '';
                        throw new MissingRequiredOptionException(
                            $option->name,
                            $option->short,
                            "value for option --{$option->name}{$shortLabel} REQUIRED, default value is not set"
                        );
                    }
                    $values[$name] = $rawValue;
                }
            } elseif ($option->short !== null && array_key_exists($option->short, $parsed)) {
                if ($option->isFlag()) {
                    $values[$name] = true;
                } else {
                    $rawValue = $parsed[$option->short];
                    if ($rawValue === false) {
                        if ($option->default !== null) {
                            $values[$name] = $option->default;
                            continue;
                        }
                        $shortLabel = $option->short ? " (-{$option->short})" : '';
                        throw new MissingRequiredOptionException(
                            $option->name,
                            $option->short,
                            "value for option --{$option->name}{$shortLabel} REQUIRED, default value is not set"
                        );
                    }
                    if ($rawValue === '') {
                        if ($option->default !== null) {
                            $values[$name] = $option->default;
                            continue;
                        }
                        $shortLabel = $option->short ? " (-{$option->short})" : '';
                        throw new MissingRequiredOptionException(
                            $option->name,
                            $option->short,
                            "value for option --{$option->name}{$shortLabel} REQUIRED, default value is not set"
                        );
                    }
                    $values[$name] = $rawValue;
                }
            } elseif ($option->default !== null) {
                $values[$name] = $option->default;
            }
        }

        if (!$help) {
            foreach ($this->options as $option) {
                if ($option->required && !array_key_exists($option->name, $values)) {
                    throw new MissingRequiredOptionException($option->name, $option->short);
                }
            }
        }

        return [
            'values' => $values,
            'help' => $help,
            'verbose' => $verbose,
        ];
    }

    private function detectMissingValueOnGetoptFailure(array $argv): void
    {
        if (count($argv) === 0) {
            return;
        }

        foreach ($this->options as $option) {
            if ($option->isFlag()) {
                continue;
            }

            $longName = '--' . $option->name;
            $shortName = $option->short !== null ? '-' . $option->short : null;

            foreach ($argv as $arg) {
                if (!is_string($arg)) {
                    continue;
                }
                if ($arg === $longName || ($shortName !== null && $arg === $shortName)) {
                    if ($option->default !== null) {
                        continue;
                    }
                    $shortLabel = $option->short ? " (-{$option->short})" : '';
                    throw new MissingRequiredOptionException(
                        $option->name,
                        $option->short,
                        "value for option --{$option->name}{$shortLabel} REQUIRED, default value is not set"
                    );
                }
                if (str_starts_with($arg, $longName . '=')) {
                    $value = substr($arg, strlen($longName) + 1);
                    if ($value === '' && $option->default !== null) {
                        continue;
                    }
                    if ($value === '' && $option->default === null) {
                        $shortLabel = $option->short ? " (-{$option->short})" : '';
                        throw new MissingRequiredOptionException(
                            $option->name,
                            $option->short,
                            "value for option --{$option->name}{$shortLabel} REQUIRED, default value is not set"
                        );
                    }
                }
            }
        }
    }

    /** @return array<string, Option> */
    public function getOptions(): array
    {
        return $this->options;
    }
}
