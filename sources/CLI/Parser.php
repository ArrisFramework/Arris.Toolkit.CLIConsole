<?php

namespace Arris\Toolkit\CLI;

use Arris\Toolkit\CLI\Exceptions\MissingRequiredOptionException;
use RuntimeException;

/**
 * Парсер CLI-опций (токен-парсер)
 */
final class Parser
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
        $longMap = [];
        $shortMap = [];
        foreach ($this->options as $option) {
            $longMap[$option->name] = $option;
            if ($option->short !== null && $option->short !== '') {
                $shortMap[$option->short] = $option;
            }
        }

        $tokens = array_slice($argv, 1);
        $count = count($tokens);
        $i = 0;
        $afterDoubleDash = false;
        $values = [];
        $help = false;
        $verbose = false;

        while ($i < $count) {
            $token = $tokens[$i];

            if (!is_string($token)) {
                $i++;
                continue;
            }

            if ($afterDoubleDash) {
                $i++;
                continue;
            }

            if ($token === '--') {
                $afterDoubleDash = true;
                $i++;
                continue;
            }

            if ($token === '-h' || $token === '--help') {
                $help = true;
                $i++;
                continue;
            }

            if ($token === '-v' || $token === '--verbose') {
                $verbose = true;
                $i++;
                continue;
            }

            if (str_starts_with($token, '--')) {
                if (str_contains($token, '=')) {
                    $pos = strpos($token, '=');
                    $name = substr($token, 2, $pos - 2);
                    $value = substr($token, $pos + 1);
                    $originalToken = $token;

                    $explicitEmptyQuotes = (str_ends_with($originalToken, '=""') || str_ends_with($originalToken, "=''"));

                    if ($value === '""' || $value === "''") {
                        $value = '';
                    }

                    if (!array_key_exists($name, $longMap)) {
                        $i++;
                        continue;
                    }

                    $option = $longMap[$name];

                    if ($option->isFlag()) {
                        $values[$name] = true;
                        $i++;
                        continue;
                    }

                    if ($option->type === OptionType::WithValue || $option->type === OptionType::WithOptionalValue) {
                        if ($value === '') {
                            if ($explicitEmptyQuotes) {
                                $values[$name] = '';
                                $i++;
                                continue;
                            }
                            if ($option->default !== null) {
                                $values[$name] = $option->default;
                            } else {
                                $shortLabel = $option->short ? " (-{$option->short})" : '';
                                throw new MissingRequiredOptionException(
                                    $option->name,
                                    $option->short,
                                    "value for option --{$option->name}{$shortLabel} REQUIRED, default value is not set"
                                );
                            }
                            $i++;
                            continue;
                        }
                        $values[$name] = $value;
                        $i++;
                        continue;
                    }

                    $values[$name] = $value;
                    $i++;
                    continue;
                }

                $name = substr($token, 2);
                if (!array_key_exists($name, $longMap)) {
                    $i++;
                    continue;
                }

                $option = $longMap[$name];

                if ($option->isFlag()) {
                    $values[$name] = true;
                    $i++;
                    continue;
                }

                if ($option->type === OptionType::WithValue || $option->type === OptionType::WithOptionalValue) {
                    $shortLabel = $option->short ? " (-{$option->short})" : '';
                    throw new MissingRequiredOptionException(
                        $option->name,
                        $option->short,
                        "value for option --{$option->name}{$shortLabel} REQUIRED, default value is not set"
                    );
                }

                $i++;
                continue;
            }

            if (str_starts_with($token, '-') && $token !== '-') {
                if (strlen($token) === 2) {
                    $shortKey = $token[1];
                    if (!array_key_exists($shortKey, $shortMap)) {
                        $i++;
                        continue;
                    }

                    $option = $shortMap[$shortKey];
                    $name = $option->name;

                    if ($option->isFlag()) {
                        $values[$name] = true;
                        $i++;
                        continue;
                    }

                    if ($option->type === OptionType::WithValue || $option->type === OptionType::WithOptionalValue) {
                        if ($i + 1 >= $count) {
                            if ($option->default !== null) {
                                $values[$name] = $option->default;
                                $i++;
                                continue;
                            }
                            $shortLabel = $option->short ? " (-{$option->short})" : '';
                            throw new MissingRequiredOptionException(
                                $option->name,
                                $option->short,
                                "value for option --{$option->name}{$shortLabel} REQUIRED, default value is not set"
                            );
                        }

                        $nextToken = $tokens[$i + 1];
                        if (!is_string($nextToken)) {
                            if ($option->default !== null) {
                                $values[$name] = $option->default;
                                $i++;
                                continue;
                            }
                            $shortLabel = $option->short ? " (-{$option->short})" : '';
                            throw new MissingRequiredOptionException(
                                $option->name,
                                $option->short,
                                "value for option --{$option->name}{$shortLabel} REQUIRED, default value is not set"
                            );
                        }

                        if (str_starts_with($nextToken, '-')) {
                            if ($option->default !== null) {
                                $values[$name] = $option->default;
                                $i++;
                                continue;
                            }
                            $shortLabel = $option->short ? " (-{$option->short})" : '';
                            throw new MissingRequiredOptionException(
                                $option->name,
                                $option->short,
                                "value for option --{$option->name}{$shortLabel} REQUIRED, default value is not set"
                            );
                        }

                        if ($nextToken === '""' || $nextToken === "''") {
                            $nextToken = '';
                        }

                        $values[$name] = $nextToken;
                        $i += 2;
                        continue;
                    }

                    $values[$name] = true;
                    $i++;
                    continue;
                }

                if (strlen($token) > 2) {
                    $len = strlen($token);
                    for ($j = 1; $j < $len; $j++) {
                        $c = $token[$j];
                        if (!array_key_exists($c, $shortMap)) {
                            throw new RuntimeException("Unknown option '-{$c}'");
                        }
                        $opt = $shortMap[$c];
                        if (!$opt->isFlag()) {
                            throw new RuntimeException("Option '-{$c}' cannot be combined");
                        }
                        $values[$opt->name] = true;
                    }
                    $i++;
                    continue;
                }

                $i++;
                continue;
            }

            $i++;
            continue;
        }

        foreach ($this->options as $name => $option) {
            if (array_key_exists($name, $values)) {
                continue;
            }
            if ($option->default !== null) {
                $values[$name] = $option->default;
                continue;
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

    /** @return array<string, Option> */
    public function getOptions(): array
    {
        return $this->options;
    }
}
