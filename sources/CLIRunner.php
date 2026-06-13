<?php

namespace Arris\Toolkit;

use Arris\Toolkit\CLI\Exceptions\MissingRequiredOptionException;
use Arris\Toolkit\CLI\Option;
use Arris\Toolkit\CLI\OptionType;
use Arris\Toolkit\CLI\Parser;
use Closure;

/**
 * Оркестратор CLI: регистрация → парсинг → выполнение
 */
final class CLIRunner
{
    private Parser $parser;
    /** @var array<string, mixed> */
    private array $optionsValues = [];
    /** @var array<string, mixed> */
    private array $variables = [];
    private ?string $scriptDescription;
    private bool $verbose = false;

    public function __construct(?string $scriptDescription = null)
    {
        $this->parser = new Parser();
        $this->scriptDescription = $scriptDescription;
    }

    public function register(
        string             $option,
        ?string            $short = null,
        string             $description = '',
        OptionType         $type = OptionType::Flag,
        mixed              $default = null,
        ?bool              $required = null,
        Closure|array|null $handler = null,
    ): self {
        $this->parser->add(
            new Option($option, $short, $description, $type, $default, $required, $handler)
        );
        return $this;
    }

    public function has(string $option): bool
    {
        return array_key_exists($option, $this->optionsValues);
    }

    public function get(string $option): mixed
    {
        return $this->optionsValues[$option] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $this->variables[$key] = $value;
    }

    public function getVar(string $key): mixed
    {
        return $this->variables[$key] ?? null;
    }

    public function isVerbose(): bool
    {
        return $this->verbose;
    }

    public function say(string $message, bool $always = false): void
    {
        if ($always || $this->verbose) {
            CLIConsole::say($message);
        }
    }

    public function run(): bool
    {
        global $argv;

        try {
            $result = $this->parser->parse($argv ?? []);
        } catch (MissingRequiredOptionException $e) {
            $this->say("<font color='red'>Error: {$e->getMessage()}</font>", true);
            $this->say("<font color='yellow'>Use --help for available options</font>", true);
            exit(2);
        }

        $this->optionsValues = $result['values'];
        $this->verbose = $result['verbose'];

        if ($result['help']) {
            $this->showHelp();
            return false;
        }

        if (empty($this->optionsValues) && !$this->verbose) {
            $this->showHelp();
            return false;
        }

        $this->execute();
        return true;
    }

    private function execute(): void
    {
        foreach ($this->parser->getOptions() as $option) {
            if ($this->has($option->name) && $option->handler !== null) {
                $value = $this->get($option->name);
                ($option->handler)($value, $this);
            }
        }
    }

    private function showHelp(): void
    {
        if ($this->scriptDescription !== null) {
            $this->say($this->scriptDescription, true);
            $this->say('', true);
        }

        $this->say("<font color='green'>Available options:</font>", true);

        $builtinOptions = [
            [
                'label' => '-h, --help',
                'label_with_tags' => "<font color='yellow'>-h, --help</font>",
                'description' => 'Show this help',
                'suffix' => '',
            ],
            [
                'label' => '-v, --verbose',
                'label_with_tags' => "<font color='yellow'>-v, --verbose</font>",
                'description' => 'Enable verbose output',
                'suffix' => '',
            ],
        ];

        $userOptions = [];
        foreach ($this->parser->getOptions() as $option) {
            $shortPart = $option->short ? "-{$option->short}, " : '    ';
            $valueSuffix = match ($option->type) {
                OptionType::Flag => '',
                OptionType::WithValue => '=<value>',
                OptionType::WithOptionalValue => '[=<value>]',
            };
            $requiredMark = $option->required
                ? " <font color='red'>[required]</font>"
                : '';

            $label = "{$shortPart}--{$option->name}{$valueSuffix}";
            $userOptions[] = [
                'label' => $label,
                'label_with_tags' => "<font color='yellow'>{$label}</font>",
                'description' => $option->description,
                'suffix' => $requiredMark,
            ];
        }

        $allOptions = array_merge($builtinOptions, $userOptions);

        $maxLength = 0;
        foreach ($allOptions as $opt) {
            $maxLength = max($maxLength, strlen($opt['label']));
        }

        $padding = $maxLength + 4;

        foreach ($allOptions as $opt) {
            $paddingNeeded = $padding - strlen($opt['label']);
            $paddingSpaces = str_repeat(' ', $paddingNeeded);

            $this->say(
                "  {$opt['label_with_tags']}{$paddingSpaces}{$opt['description']}{$opt['suffix']}",
                true
            );
        }
    }
}