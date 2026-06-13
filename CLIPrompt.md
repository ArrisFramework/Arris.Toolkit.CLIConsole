# README для CLIPrompt

# CLIPrompt — интерактивный ввод для PHP CLI

`CLIPrompt` — утилита для интерактивного ввода в CLI-скриптах с поддержкой валидации, скрытого ввода (для паролей) и кроссплатформенности.

## Особенности

- ✅ **Валидация по regex** — цикл до получения корректного ввода
- 🔒 **Скрытый ввод** — для паролей и чувствительных данных
- 🖥 **Кроссплатформенность** — поддержка Unix/Linux/macOS и Windows
- 🎯 **Простой API** — три метода для разных сценариев
- ⚡ **Кэширование** — обнаружение shell выполняется один раз
- 🛡 **PHP 8.2 ready** — типизация, match expressions

## Быстрый старт

```php
<?php
use Arris\Toolkit\CLIPrompt;

// Простой ввод
$name = CLIPrompt::prompt("Enter your name: ");

// Ввод с валидацией
$email = CLIPrompt::readline(
    prompt: "Enter email: ",
    allowedPattern: '/^[^@]+@[^@]+\.[^@]+$/'
);

// Скрытый ввод (пароль)
$password = CLIPrompt::hiddenPrompt("Enter password: ");
```

## Методы

### `prompt(string $message = ''): string`

Простой запрос ввода через STDIN.

```php
// Без сообщения
$answer = CLIPrompt::prompt();
echo "Please type something: "; // нужно выводить отдельно
// Пользователь вводит текст

// С сообщением
$answer = CLIPrompt::prompt("Your name: ");
// Выводится "Your name: " и ожидается ввод
```

**Возвращает**: строку без trailing newline

**Исключения**: `RuntimeException` если не удалось открыть STDIN

### `readline(string $prompt, string $allowedPattern = '/.*/', bool $strictMode = false): string|false`

Запрос ввода с валидацией по regex. Цикл повторяется, пока пользователь не введёт значение, соответствующее паттерну.

```php
// Любой ввод
$any = CLIPrompt::readline("Enter anything: ");

// Только цифры
$number = CLIPrompt::readline(
    prompt: "Enter number: ",
    allowedPattern: '/^\d+$/'
);

// Email
$email = CLIPrompt::readline(
    prompt: "Email: ",
    allowedPattern: '/^[^@]+@[^@]+\.[^@]+$/'
);

// Yes/No
$confirm = CLIPrompt::readline(
    prompt: "Continue? (y/n): ",
    allowedPattern: '/^[yn]$/i'
);
```

#### Режимы валидации

**Non-strict mode** (по умолчанию) — автоматически добавляет `/` если их нет:

```php
// Эти вызовы эквивалентны:
CLIPrompt::readline("Number: ", '/^\d+$/');
CLIPrompt::readline("Number: ", '^\d+$');
CLIPrompt::readline("Number: ", '/^\d+$');
CLIPrompt::readline("Number: ", '^\d+$/');
```

**Strict mode** — требует `/` с обеих сторон, иначе возвращает `false`:

```php
// OK — вернёт введённое число
CLIPrompt::readline("Number: ", '/^\d+$/', strictMode: true);

// Вернёт false сразу, без запроса ввода
CLIPrompt::readline("Number: ", '^\d+$', strictMode: true);
```

**Возвращает**: введённую строку или `false` при ошибке (невалидный паттерн или EOF)

### `hiddenPrompt(string $message = 'Password: ', bool $allowFallback = false): string`

Скрытый ввод — символы не отображаются при вводе. Используется для паролей и токенов.

```php
// Базовое использование
$password = CLIPrompt::hiddenPrompt();
// Выводится "Password: " и ожидается скрытый ввод

// С кастомным сообщением
$apiKey = CLIPrompt::hiddenPrompt("API Key: ");

// С fallback к обычному вводу
$token = CLIPrompt::hiddenPrompt(
    message: "Token: ",
    allowFallback: true
);
```

#### Как работает

**Unix/Linux/macOS**:
- Определяет доступный shell (bash, zsh, ksh, csh, sh)
- Использует `stty -echo` для отключения эха
- Кэширует найденный shell для повторных вызовов

**Windows**:
- Использует PowerShell с `Read-Host -AsSecureString`
- Конвертирует SecureString обратно в обычную строку

**Fallback**:
- Если скрытый ввод невозможен и `allowFallback = true` — использует обычный `prompt()`
- Если `allowFallback = false` — выбрасывает исключение

**Возвращает**: введённую строку без trailing newline

**Исключения**: `RuntimeException` если скрытый ввод невозможен и fallback запрещён

## Примеры использования

### Интерактивный мастер настройки

```php
use Arris\Toolkit\CLIPrompt;
use Arris\Toolkit\CLIConsole;

CLIConsole::say("<font color='cyan'>Setup Wizard</font>");
CLIConsole::say("<hr width='50'/>");

$name = CLIPrompt::readline(
    prompt: "Your name: ",
    allowedPattern: '/^[A-Za-z\s]{2,50}$/'
);

$email = CLIPrompt::readline(
    prompt: "Email: ",
    allowedPattern: '/^[^@]+@[^@]+\.[^@]+$/'
);

$password = CLIPrompt::hiddenPrompt("Password: ");
$confirm = CLIPrompt::hiddenPrompt("Confirm password: ");

if ($password !== $confirm) {
    CLIConsole::say("<font color='red'>Passwords don't match!</font>");
    exit(1);
}

CLIConsole::say("<br><font color='green'>Setup complete!</font>");
CLIConsole::say("Welcome, <strong>{$name}</strong>!");
```

### CLI-утилита с конфигурацией

```php
use Arris\Toolkit\CLIPrompt;

class ConfigWizard {
    public static function run(): array {
        $config = [];
        
        $config['host'] = CLIPrompt::readline(
            prompt: "Database host [localhost]: ",
            allowedPattern: '/^[\w.-]+$/'
        ) ?: 'localhost';
        
        $config['port'] = CLIPrompt::readline(
            prompt: "Database port [3306]: ",
            allowedPattern: '/^\d{1,5}$/'
        ) ?: '3306';
        
        $config['user'] = CLIPrompt::readline(
            prompt: "Database user: ",
            allowedPattern: '/^[\w]+$/'
        );
        
        $config['password'] = CLIPrompt::hiddenPrompt("Database password: ");
        
        return $config;
    }
}

$config = ConfigWizard::run();
```

### Подтверждение действия

```php
use Arris\Toolkit\CLIPrompt;
use Arris\Toolkit\CLIConsole;

CLIConsole::say("<font color='yellow'>Warning: This will delete all data!</font>");

$confirm = CLIPrompt::readline(
    prompt: "Type 'DELETE' to confirm: ",
    allowedPattern: '/^DELETE$/'
);

if ($confirm === 'DELETE') {
    CLIConsole::say("<font color='red'>Deleting...</font>");
    // ... удаление ...
} else {
    CLIConsole::say("<font color='green'>Cancelled</font>");
}
```

### Многошаговая валидация

```php
use Arris\Toolkit\CLIPrompt;

// Шаг 1: Имя пользователя
$username = CLIPrompt::readline(
    prompt: "Username (3-20 chars, alphanumeric): ",
    allowedPattern: '/^[a-zA-Z0-9]{3,20}$/'
);

// Шаг 2: Email
$email = CLIPrompt::readline(
    prompt: "Email: ",
    allowedPattern: '/^[^@]+@[^@]+\.[^@]+$/'
);

// Шаг 3: Возраст
$age = CLIPrompt::readline(
    prompt: "Age (18-99): ",
    allowedPattern: '/^(1[89]|[2-8]\d|9[0-9])$/'
);

// Шаг 4: Пароль
$password = CLIPrompt::hiddenPrompt("Password (min 8 chars): ");
```

## Обработка ошибок

### Невалидный regex-паттерн

```php
$result = CLIPrompt::readline("Test: ", '/[invalid/');
// Вернёт false, цикл не запустится
```

### EOF (Ctrl+D / Ctrl+Z)

```php
$result = CLIPrompt::readline("Input: ");
// Если пользователь нажмёт Ctrl+D (Unix) или Ctrl+Z (Windows)
// readline() вернёт false, метод вернёт false
```

### Невозможность скрытого ввода

```php
try {
    $password = CLIPrompt::hiddenPrompt(
        message: "Password: ",
        allowFallback: false
    );
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    // "Error: Could not prompt for input in a secure fashion"
}
```

## Интеграция с CLIRunner

```php
use Arris\Toolkit\CLIRunner;
use Arris\Toolkit\CLIOptionType;
use Arris\Toolkit\CLIPrompt;
use Arris\Toolkit\CLIConsole;

$cli = new CLIRunner("<font color='cyan'>User Manager</font>");

$cli->register(
    option: 'add-user',
    description: 'Add new user interactively',
    handler: function ($value, CLIRunner $runner) {
        CLIConsole::say("<font color='yellow'>Adding new user...</font>");
        
        $username = CLIPrompt::readline(
            prompt: "Username: ",
            allowedPattern: '/^[a-zA-Z0-9_]{3,20}$/'
        );
        
        $email = CLIPrompt::readline(
            prompt: "Email: ",
            allowedPattern: '/^[^@]+@[^@]+\.[^@]+$/'
        );
        
        $password = CLIPrompt::hiddenPrompt("Password: ");
        
        CLIConsole::say("<font color='green'>User {$username} created!</font>");
    }
);

$cli->run();
```

## API Reference

### Методы

| Метод | Описание | Возвращает |
|---|---|---|
| `prompt(string $message = ''): string` | Простой запрос ввода | Строка без newline |
| `readline(string $prompt, string $allowedPattern = '/.*/', bool $strictMode = false): string\|false` | Запрос с валидацией | Строка или false |
| `hiddenPrompt(string $message = 'Password: ', bool $allowFallback = false): string` | Скрытый ввод | Строка без newline |

### Параметры `readline()`

| Параметр | Тип | По умолчанию | Описание |
|---|---|---|---|
| `prompt` | string | — | Текст приглашения |
| `allowedPattern` | string | `'/.*/'` | Regex для валидации |
| `strictMode` | bool | `false` | Требовать `/` в начале и конце |

### Параметры `hiddenPrompt()`

| Параметр | Тип | По умолчанию | Описание |
|---|---|---|---|
| `message` | string | `'Password: '` | Сообщение перед запросом |
| `allowFallback` | bool | `false` | Разрешить fallback к обычному prompt |

## Требования

- PHP 8.2+
- Модуль `readline` для метода `readline()` (опционально)
- Доступ к STDIN для `prompt()` и `hiddenPrompt()`
- Для `hiddenPrompt()`:
    - **Unix/Linux/macOS**: доступный shell (bash, zsh, ksh, csh, sh)
    - **Windows**: PowerShell

## Ограничения

1. **`readline()`** требует модуль `readline` — если его нет, используйте `prompt()`
2. **`hiddenPrompt()`** на Windows требует PowerShell 2.0+
3. **Regex-паттерны** должны быть валидными — невалидные паттерны приводят к `false`
4. **Strict mode** в `readline()` требует точного формата `/pattern/`

## Лицензия

MIT

## Автор

Karel Wintersky — [github.com/KarelWintersky/Arris](https://github.com/KarelWintersky/Arris)