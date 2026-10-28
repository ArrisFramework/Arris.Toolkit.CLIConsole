<?php

namespace Arris\Toolkit;

use Arris\Toolkit\CLI\Exceptions\MissingRequiredOptionException;
use Arris\Toolkit\CLI\Option;
use Arris\Toolkit\CLI\OptionType;
use Arris\Toolkit\CLI\Parser;
use Arris\Toolkit\CLI\ParserNative;
use Closure;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use Throwable;

/**
 * Оркестратор CLI: регистрация → парсинг → выполнение
 */
final class CLIRunner
{
    private Parser|ParserNative $parser;

    /** @var array<string, mixed> */
    private array $optionsValues = [];

    /** @var array<string, mixed> */
    private array $variables = [];

    private ?string $scriptDescription;

    private bool $verbose = false;

    /** @var array<class-string, object> Зарегистрированные инстансы классов */
    private array $classInstances = [];

    /** @var array<string, string|null> Порядок групп для help: ключ - имя группы, значение - описание группы или null */
    private array $helpGroupOrder = [];

    public function __construct(?string $scriptDescription = null, bool $useLegacyParser = false)
    {
        $this->parser = $useLegacyParser ? new ParserNative() : new Parser();
        $this->scriptDescription = $scriptDescription;
    }

    /**
     * Задать порядок групп для вывода в help.
     * Формат: ['GroupName' => 'Group description', 'AnotherGroup' => null, 'ThirdGroup']
     * Если значение - строка, используется как заголовок группы с описанием
     * Если значение - null или числовой ключ - используется только имя группы
     */
    public function setHelpGroupOrder(array $order): self
    {
        $this->helpGroupOrder = [];
        foreach ($order as $key => $value) {
            if (is_int($key)) {
                $this->helpGroupOrder[$value] = null;
            } else {
                $this->helpGroupOrder[$key] = $value;
            }
        }
        return $this;
    }

    public function registerClass(string $className, object $instance): self
    {
        if (!($instance instanceof $className)) {
            throw new RuntimeException(
                "Instance must be of class {$className} or its descendant"
            );
        }
        $this->classInstances[$className] = $instance;
        return $this;
    }

    public function register(
        string              $option,
        ?string             $short = null,
        string              $description = '',
        OptionType          $type = OptionType::Flag,
        mixed               $default = null,
        ?bool               $required = null,
        Closure|array|null  $handler = null,
        ?string             $group = null,
    ): self {
        $this->parser->add(
            new Option($option, $short, $description, $type, $default, $required, $handler, $group)
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
                $handler = $this->resolveHandler($option->handler);
                $handler($value, $this);
            }
        }
    }

    /**
     * @param Closure|array $handler
     * @return Closure
     */
    private function resolveHandler(Closure|array $handler): Closure
    {
        if ($handler instanceof Closure) {
            return $handler;
        }

        if (!is_array($handler) || count($handler) !== 2) {
            throw new RuntimeException('Handler must be a Closure or [class/object, method]');
        }

        [$classOrObject, $method] = $handler;

        // Объект передан напрямую — используем как есть
        if (is_object($classOrObject)) {
            return fn(...$args) => $classOrObject->$method(...$args);
        }

        // Строка (имя класса) — резолвим инстанс
        if (is_string($classOrObject)) {
            try {
                $reflection = new ReflectionMethod($classOrObject, $method);
            } catch (ReflectionException $e) {
                throw new RuntimeException(
                    "Method {$classOrObject}::{$method}() does not exist",
                    0,
                    $e
                );
            }

            // Статический метод — вызываем напрямую
            if ($reflection->isStatic()) {
                return fn(...$args) => $classOrObject::$method(...$args);
            }

            // Нестатический метод: сначала ищем зарегистрированный инстанс
            $instance = $this->classInstances[$classOrObject] ?? null;

            if ($instance === null) {
                // Инстанс не зарегистрирован — создаём через new
                try {
                    $instance = new $classOrObject();
                } catch (Throwable $e) {
                    throw new RuntimeException(
                        "Cannot instantiate {$classOrObject} for handler. " .
                        "Use \$cli->registerClass({$classOrObject}::class, new {$classOrObject}(...)) " .
                        "to provide a pre-configured instance.",
                        0,
                        $e
                    );
                }
            }

            return fn(...$args) => $instance->$method(...$args);
        }

        throw new RuntimeException('Invalid handler format');
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

        // Выводим встроенные опции
        $maxLength = 0;
        foreach ($builtinOptions as $opt) {
            $maxLength = max($maxLength, strlen($opt['label']));
        }

        $padding = $maxLength + 4;

        foreach ($builtinOptions as $opt) {
            $paddingNeeded = $padding - strlen($opt['label']);
            $paddingSpaces = str_repeat(' ', $paddingNeeded);

            $this->say(
                "  {$opt['label_with_tags']}{$paddingSpaces}{$opt['description']}{$opt['suffix']}",
                true
            );
        }

        // Группируем пользовательские опции
        $groups = [];
        $ungrouped = [];

        foreach ($this->parser->getOptions() as $option) {
            $item = [
                'option' => $option,
                'label' => $this->formatOptionLabel($option),
                'label_with_tags' => '',
                'description' => $option->description,
                'suffix' => $option->required ? " <font color='red'>[required]</font>" : '',
            ];
            $item['label_with_tags'] = "<font color='yellow'>{$item['label']}</font>";

            if ($option->group === null || $option->group === '') {
                $ungrouped[] = $item;
            } else {
                if (!isset($groups[$option->group])) {
                    $groups[$option->group] = [];
                }
                $groups[$option->group][] = $item;
            }
        }

        // Выводим группы в заданном порядке
        if (!empty($this->helpGroupOrder)) {
            foreach ($this->helpGroupOrder as $groupName => $groupDesc) {
                if (empty($groups[$groupName])) {
                    continue;
                }
                $this->outputGroup($groupName, $groupDesc, $groups[$groupName]);
                unset($groups[$groupName]);
            }
        }

        // Выводим оставшиеся группы
        foreach ($groups as $groupName => $items) {
            $this->outputGroup($groupName, null, $items);
        }

        // Выводим негруппированные опции
        if (!empty($ungrouped)) {
            $this->outputGroup(null, null, $ungrouped);
        }

        CLIConsole::say();
    }

    private function formatOptionLabel(\Arris\Toolkit\CLI\Option $option): string
    {
        $shortPart = $option->short ? "-{$option->short}, " : '    ';
        $valueSuffix = match ($option->type) {
            OptionType::Flag => '',
            OptionType::WithValue => '=<value>',
            OptionType::WithOptionalValue => '[=<value>]',
        };
        return "{$shortPart}--{$option->name}{$valueSuffix}";
    }

    private function outputGroup(?string $groupName, ?string $groupDesc, array $items): void
    {
        $this->say('', true);
        if ($groupName === null) {
            $this->say("<font color='green'>Commands:</font>", true);
        } else {
            if ($groupDesc !== null && $groupDesc !== '') {
                $this->say("<font color='green'>{$groupName}:</font> <font color='gray'>{$groupDesc}</font>", true);
            } else {
                $this->say("<font color='green'>{$groupName}:</font>", true);
            }
        }

        $maxLength = 0;
        foreach ($items as $item) {
            $maxLength = max($maxLength, strlen($item['label']));
        }

        $padding = $maxLength + 4;

        foreach ($items as $item) {
            $paddingNeeded = $padding - strlen($item['label']);
            $paddingSpaces = str_repeat(' ', $paddingNeeded);

            $this->say(
                "  {$item['label_with_tags']}{$paddingSpaces}{$item['description']}{$item['suffix']}",
                true
            );
        }
    }
}