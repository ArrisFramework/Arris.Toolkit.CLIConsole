# CLIRunner — декларативный CLI-фреймворк для PHP 8.2

`CLIRunner` — это лёгкий, декларативный инструмент для создания CLI-скриптов на PHP. Объединяет регистрацию опций, парсинг аргументов, вызов обработчиков и генерацию справки в единый API.

## Особенности

- 🎯 **Декларативная регистрация** — опция описывается один раз (имя, тип, описание, обработчик)
- 📖 **Автоматический help** — справка генерируется из зарегистрированных опций с выравниванием
- ✅ **Валидация обязательных опций** — понятные ошибки при отсутствии required-опций
- 🔧 **Три типа опций**: флаги (`--verbose`), обязательное значение (`--config=file`), опциональное значение (`--log[=level]`)
- 📦 **Dependency injection** — `registerClass()` для передачи готовых инстансов в обработчиках
- 🎨 **Встроенный `--verbose` режим** — условный вывод через `$runner->say()`
- 🛡 **PHP 8.2 ready** — enums, readonly-классы, union-типы
- ⚙️ **Два парсера** — новый токен-парсер по умолчанию, legacy `getopt()` через `new CLIRunner(..., true)` или `ParserNative`

## Конструктор

```php
$cli = new CLIRunner(?string $scriptDescription = null, bool $useLegacyParser = false);
```

- `$scriptDescription` — описание скрипта для вывода в `--help`
- `$useLegacyParser` — при `true` используется `ParserNative` на основе `getopt()`. По умолчанию `false` (новый токен-парсер).

## Быстрый старт

```php
<?php
require_once __DIR__ . '/sources/CLIRunner.php';

$cli = new CLIRunner("<font color='cyan'>My awesome CLI script</font>");

$cli->register(
    option: 'clear:redis',
    short: 'r',
    description: 'Clear Redis keys for site',
    handler: fn($value, $runner) => CLIConsole::say("<font color='green'>Redis cleared</font>"),
    group: 'Redis'
);

$cli->register(
    option: 'config',
    short: 'c',
    description: 'Path to config file',
    type: CLIOptionType::WithValue,
    default: 'config.json',
    handler: function (string $path, CLIRunner $runner) {
        $runner->set('config_path', $path);
    },
);

$cli->run();

// Использование значений после парсинга
if ($configPath = $cli->getVar('config_path')) {
    CLIConsole::say("Loaded config: {$configPath}");
}
```

Запуск:

```bash
php script.php --help
# My awesome CLI script
#
# Available options:
#   -h, --help              Show this help
#   -v, --verbose           Enable verbose output
#   -r, --clear:redis       Clear Redis keys for site
#   -c, --config=<value>    Path to config file
```

## Типы опций

```php
enum CLIOptionType: string
{
    case Flag = 'flag';                       // --verbose
    case WithValue = 'with_value';            // --config=file
    case WithOptionalValue = 'with_optional'; // --log[=level]
}
```

### 1. Флаг (`Flag`)

Булева опция — присутствие означает `true`, отсутствие — нет значения.

```php
$cli->register(
    option: 'dry-run',
    short: 'n',
    description: 'Dry run, no actual changes',
    handler: fn(bool $dryRun, $runner) => $runner->set('dry_run', $dryRun),
);
```

### 2. Обязательное значение (`WithValue`)

Опция типа `WithValue` требует **значение**, если сама опция передана. 

**Формы передачи (новый парсер):**
- `--config=file` — через `=` (единственный поддерживаемый способ для длинного ключа)
- `-c file` — короткий ключ через пробел
- Пустое значение можно передать как `-c ""` (отдельный токен в кавычках). `--config=` (пустое после `=` без кавычек) трактуется как **отсутствие значения** и вызывает ошибку, если нет `default`

**Взаимосвязь `required` и `default`:**
- `required` в `register()` означает **обязательность самой опции** — опция должна быть передана в CLI. По умолчанию `false`.
- `default` — значение, которое подставляется, если опция **вообще не передана**.
- **Автоматического выставления `required=true` при `WithValue` без `default` больше нет.** Раньше это поведение существовало, сейчас убрано намеренно. Обязательность самой опции задаётся только явным `required: true`.
- Если опция типа `WithValue` передана, но значение не предоставлено и `default === null` — парсер бросает `MissingRequiredOptionException` с сообщением `value for option --{name} (-{short}) REQUIRED, default value is not set` и скрипт завершается с кодом `2`.
- Если опция типа `WithValue` **не передана вообще** и нет `default` и `required === false` — опция просто отсутствует в `$values`, обработчик не вызывается.

```php
// Опция не обязательна. Если передана - значение ОБЯЗАТЕЛЬНО. Если не передана - игнорируем.
$cli->register(
    option: 'delete',
    short: 'd',
    type: CLIOptionType::WithValue,
    description: 'Удалить пакет из кэша: --delete=vendor/package-name',
    handler: function ($value, $cli) {
        var_dump($value);
    },
);

// То же самое, но если не передана - подставляется default
$cli->register(
    option: 'config',
    short: 'c',
    type: CLIOptionType::WithValue,
    default: 'config.json',
    handler: function ($path, $cli) {
        // ...
    },
);

// Сама опция обязательна к передаче
$cli->register(
    option: 'database',
    type: CLIOptionType::WithValue,
    required: true,
    description: 'Database DSN',
);
```

### 3. Опциональное значение (`WithOptionalValue`)

Опция может быть передана без значения (`--log`) или с ним (`--log=debug`).

```php
$cli->register(
    option: 'log',
    short: 'l',
    type: CLIOptionType::WithOptionalValue,
    description: 'Enable logging [level]',
    default: 'info',
    handler: fn(string $level, $runner) => $runner->set('log_level', $level),
);
```

## Обязательные опции

Параметр `$required` у `register()` отвечает **только** за то, что сама опция должна быть передана. Он **не** связан с тем, нужно ли значение для `WithValue`.

```php
$cli->register(
    option: 'database',
    type: CLIOptionType::WithValue,
    required: true,  // сама опция обязательна к передаче
    description: 'Database DSN',
);
```

При отсутствии обязательной опции (когда `required: true` и опция не передана) скрипт завершается с кодом `2`:

```bash
$ php script.php
Error: Missing required option: --database
Use --help for available options
```

## Обработчики (handlers)

Обработчик — это то, что выполняется, когда опция передана. Поддерживаются 5 форматов:

### 1. Closure

```php
$cli->register(
    option: 'say',
    handler: fn($value, $runner) => CLIConsole::say('Hello!'),
);
```

### 2. Нестатический метод класса (автоматическое инстанциирование)

```php
class MyService {
    public function run($value, CLIRunner $runner) { /* ... */ }
}

$cli->register(option: 'run', handler: [MyService::class, 'run']);
```

Если класс имеет конструктор без параметров — экземпляр создаётся автоматически.

### 3. Статический метод

```php
class MyService {
    public static function run($value, CLIRunner $runner) { /* ... */ }
}

$cli->register(option: 'run', handler: [MyService::class, 'run']);
```

### 4. Готовый объект

```php
$service = new MyService();
$cli->register(option: 'run', handler: [$service, 'run']);
```

### 5. Dependency Injection через `registerClass`

Для классов с зависимостями в конструкторе:

```php
class RedisService {
    public function __construct(private array $config) {}
    public function clear($value, CLIRunner $runner) { /* ... */ }
}

// Регистрируем настроенный инстанс
$cli->registerClass(RedisService::class, new RedisService(['host' => '127.0.0.1']));

// Handler использует зарегистрированный инстанс
$cli->register(
    option: 'clear-redis',
    handler: [RedisService::class, 'clear'],
);
```

Если инстанс не зарегистрирован, а у класса есть обязательные параметры конструктора — будет выброшено исключение с понятной подсказкой:

```
Cannot instantiate RedisService for handler.
Use $cli->registerClass(RedisService::class, new RedisService(...)) to provide a pre-configured instance.
```

## Встроенные опции

### `--help` / `-h`

Показывает справку. Всегда выводится независимо от `--verbose`.

### `--verbose` / `-v`

Магическая опция, присутствует во всех скриптах. Управляет выводом через `$runner->say()`.

```php
$cli->register(
    option: 'process',
    handler: function ($value, CLIRunner $runner) {
        // Выведется только с --verbose
        $runner->say("<font color='yellow'>Starting process...</font>");
        
        // Всегда выведется
        $runner->say("<font color='green'>Done</font>", always: true);
    },
);
```

## Встроенный help

При запуске без опций или с `--help` генерируется автоматически выровненная справка:

```
My awesome CLI script

Available options:
  -h, --help              Show this help
  -v, --verbose           Enable verbose output
  -r, --clear:redis       Clear Redis keys for site
  -c, --config=<value>    Path to config file
  -d, --database=<value>  Database DSN [required]
```

Пометка `[required]` автоматически добавляется для обязательных опций в конце строки, не ломая выравнивание.

## Работа с состоянием

### Переменные приложения (`set` / `getVar`)

```php
$cli->register(
    option: 'config',
    handler: fn($path, $runner) => $runner->set('config_path', $path),
);

$cli->run();

$configPath = $cli->getVar('config_path');
```

### Получение значений опций

```php
$cli->run();

if ($cli->has('config')) {
    $path = $cli->get('config');
}

$isVerbose = $cli->isVerbose();
```

## Пример полного скрипта

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Arris\Toolkit\CLIRunner;
use Arris\Toolkit\CLIOptionType;
use Arris\Toolkit\CLIConsole;

// Класс с зависимостями
class CacheService {
    public function __construct(
        private \Redis $redis,
        private string $prefix,
    ) {}

    public function clear(bool $value, CLIRunner $runner): void {
        $runner->say("<font color='yellow'>Clearing {$this->prefix}:*</font>");
        $keys = $this->redis->keys($this->prefix . ':*');
        if ($keys) {
            $this->redis->del($keys);
        }
        $runner->say("<font color='green'>Cleared " . count($keys) . " keys</font>", always: true);
    }
}

class ImportService {
    public function __construct(private CacheService $cache) {}

    public function run(string $file, CLIRunner $runner): void {
        $runner->say("<font color='yellow'>Importing from {$file}...</font>");
        // ... импорт ...
        $this->cache->clear(true, $runner);
        $runner->say("<font color='green'>Import completed</font>", always: true);
    }
}

// --- Создание runner'а ---
$cli = new CLIRunner("<font color='cyan'>Data import tool</font>");

// --- Регистрация зависимостей ---
$redis = new \Redis();
$redis->connect('127.0.0.1');
$cache = new CacheService($redis, 'myapp');
$import = new ImportService($cache);

$cli->registerClass(CacheService::class, $cache);
$cli->registerClass(ImportService::class, $import);

// --- Регистрация опций ---
$cli->register(
    option: 'import',
    short: 'i',
    type: CLIOptionType::WithValue,
    required: true,
    description: 'File to import',
    handler: [ImportService::class, 'run'],
);

$cli->register(
    option: 'clear-cache',
    description: 'Clear cache before import',
    handler: [CacheService::class, 'clear'],
);

// --- Запуск ---
$cli->run();
```

## API Reference

### `CLIRunner`

| Метод                                                                                                                                                                                                                     | Описание |
|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---|
| `__construct(?string $scriptDescription = null, bool $useLegacyParser = false)`                                                                                                                                          | Создание runner'а с опциональным описанием скрипта. При `$useLegacyParser = true` используется `ParserNative` на основе `getopt()` |
| `register(string $option, ?string $short = null, string $description = '', CLIOptionType $type = Flag, mixed $default = null, ?bool $required = null, Closure\|array\|null $handler = null, ?string $group = null): self` | Регистрация опции |
| `registerClass(string $className, object $instance): self`                                                                                                                                                                | Регистрация готового инстанса класса |
| `run(): bool`                                                                                                                                                                                                             | Запуск обработки CLI. Возвращает `false` если был показан help |
| `has(string $option): bool`                                                                                                                                                                                               | Была ли передана опция |
| `get(string $option): mixed`                                                                                                                                                                                              | Значение опции |
| `set(string $key, mixed $value): void`                                                                                                                                                                                    | Установить переменную приложения |
| `getVar(string $key): mixed`                                                                                                                                                                                              | Получить переменную приложения |
| `isVerbose(): bool`                                                                                                                                                                                                       | Включен ли verbose-режим |
| `say(string $message, bool $always = false): void`                                                                                                                                                                        | Вывести сообщение. С `always: true` — всегда, иначе только с `--verbose` |

TODO: 

### `CLIOptionType`

| Значение | Описание |
|---|---|
| `Flag` | Булева опция (`--flag`) |
| `WithValue` | Опция с обязательным значением (`--key=value`) |
| `WithOptionalValue` | Опция с опциональным значением (`--key[=value]`) |

### `CLIParser` / `Parser` / `ParserNative`

Парсер опций. Обычно используется через `CLIRunner`, но может быть использован отдельно:

```php
$parser = new Arris\Toolkit\CLI\Parser(); // новый токен-парсер (по умолчанию)
$parser->add(new Arris\Toolkit\CLI\Option('config', 'c', 'Config file', Arris\Toolkit\CLI\OptionType::WithValue, null, false, null));

$result = $parser->parse($argv);
// ['values' => [...], 'help' => bool, 'verbose' => bool]
```

`ParserNative` — legacy парсер на основе `getopt()`. Доступен через `new CLIRunner($desc, true)`.

### `CLIMissingRequiredOptionException`

Выбрасывается при валидации:
- при отсутствии опции с `required: true`
- при передаче `WithValue` без значения и без `default` (сообщение вида `value for option --{name} (-{short}) REQUIRED, default value is not set`)

Содержит:
- `optionName` — имя опции
- `shortName` — короткое имя (если есть)

## Обработка ошибок

### Отсутствие обязательной опции

Когда опция помечена как `required: true`, но не передана:

```bash
Error: Missing required option: --config (-c)
Use --help for available options
```
Exit code: `2`

### Отсутствие значения для `WithValue`

Когда опция типа `WithValue` передана, но значение не предоставлено и нет `default`:

```bash
$ php script.php --delete
Error: value for option --delete (-d) REQUIRED, default value is not set
Use --help for available options
```

Аналогично для `--delete=` (пустое значение после `=` без кавычек), `-d` без следующего валидного значения.

Пустое значение можно передать явно: `-d ""` или в кавычках. `--delete=""` в зависимости от того, как передаст shell — в новом парсере `--delete=` трактуется как отсутствие значения, пустая строка как отдельный токен принимается.

### Невозможно инстанциировать класс

```
Cannot instantiate MyService for handler.
Use $cli->registerClass(MyService::class, new MyService(...)) to provide a pre-configured instance.
```

### Метод не существует

```
Method MyService::unknownMethod() does not exist
```

### Попытка зарегистрировать опцию с зарезервированным именем (legacy/parser behavior)

В `ParserNative` (getopt) при регистрации опции с именем `verbose` или коротким `v` бросается исключение:
```
Option name 'verbose' or short 'v' is reserved for built-in verbose mode
```

В новом токен-парсере `Parser` это ограничение снято — можно переопределить `--help/-h`, `--verbose/-v`, но делать этого **не рекомендуется**.

## Требования

- PHP 8.2+
- Модуль `readline` (опционально, для `CLIConsole::readline`)

## Лицензия

MIT

## Автор

Karel Wintersky — [github.com/KarelWintersky/Arris](https://github.com/KarelWintersky/Arris)