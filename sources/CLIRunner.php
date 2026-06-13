<?php

namespace Arris\Toolkit;

use Closure;

/**
 * Оркестратор CLI: регистрация → парсинг → выполнение
 */
final class CLIRunner
{
    private CLIParser $parser;
    /** @var array<string, mixed> Значения опций после парсинга */
    private array $optionsValues = [];
    /** @var array<string, mixed> Произвольные переменные приложения */
    private array $variables = [];

    private ?string $initialMessage = null;

    public function __construct(?string $initialMessage = null)
    {
        $this->initialMessage = $initialMessage;
        $this->parser = new CLIParser();
    }

    /**
     * Регистрация опции
     */
    public function register(
        string $option,
        ?string $short = null,
        string $description = '',
        CLIOptionType $type = CLIOptionType::Flag,
        mixed $default = null,
        Closure|array|null $handler = null,
    ): self {
        $this->parser->add(
            new CLIOption($option, $short, $description, $type, $default, $handler)
        );
        return $this;
    }

    /** Была ли передана опция */
    public function has(string $option): bool
    {
        return array_key_exists($option, $this->optionsValues);
    }

    /** Значение опции */
    public function get(string $option): mixed
    {
        return $this->optionsValues[$option] ?? null;
    }

    /** Установка произвольной переменной приложения */
    public function set(string $key, mixed $value): void
    {
        $this->variables[$key] = $value;
    }

    /** Получение произвольной переменной приложения */
    public function getVar(string $key): mixed
    {
        return $this->variables[$key] ?? null;
    }

    /**
     * Запуск обработки CLI.
     * Возвращает true если были обработаны опции, false если был показан help.
     */
    public function run(): bool
    {
        global $argv;

        $result = $this->parser->parse($argv ?? []);
        $this->optionsValues = $result['values'];

        if ($result['help'] || empty($this->optionsValues)) {
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

    public function showHelp(): void
    {
        if ($this->initialMessage) CLIConsole::say("<br>" . $this->initialMessage);

        CLIConsole::say("<br><font color='green'>Available options:</font>");
        CLIConsole::say("  <font color='yellow'>-h, --help</font>             Show this help");

        foreach ($this->parser->getOptions() as $option) {
            $shortPart = $option->short ? "-{$option->short}, " : '    ';
            $valueSuffix = match ($option->type) {
                CLIOptionType::Flag => '',
                CLIOptionType::RequiredValue => '=<value>',
                CLIOptionType::OptionalValue => '[=<value>]',
            };

            CLIConsole::say(sprintf(
                "  <font color='yellow'>%s--%s%s</font>  %s",
                $shortPart,
                $option->name,
                $valueSuffix,
                $option->description,
            ));
        }
        CLIConsole::say();
    }
}