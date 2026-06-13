# CLIConsole — HTML-to-ANSI консольный вывод для PHP 8.2

`CLIConsole` — утилита для форматированного вывода в консоль с использованием HTML-подобных тегов (`<font>`, `<strong>`, `<br>`, `<hr>`). Автоматически преобразует HTML в ANSI escape-коды в CLI-режиме и оставляет HTML для веб-режима.

## Особенности

- 🎨 **HTML-разметка в консоли** — `<font color='red'>`, `<strong>`, `<br>`, `<hr>`
- 🌐 **Двойной режим** — автоматическое определение CLI / Web через `php_sapi_name()`
- ⚙️ **Настраиваемые опции** — `strip_tags`, `decode_entities`, `hr_length` и др.
- ⌨️ **Интерактивный ввод** — `readline()` с валидацией по regex
- 📦 **Sprintf-формат** — поддержка массива аргументов `[format, ...args]`
- 🛡 **PHP 8.2 ready** — типы, константы, readonly

## Быстрый старт

```php
<?php
use Arris\Toolkit\CLIConsole;

CLIConsole::say("<font color='green'>Hello,</font> <strong>world</strong>!");
CLIConsole::say("<font color='yellow'>Warning:</font> something happened");
CLIConsole::say("<hr color='cyan' width='40'/>");
```

Вывод в терминале:
```
Hello, world!         (зелёный + белый bold)
Warning: something happened  (жёлтый + белый bold)
----------------------------------------  (cyan)
```

## Поддерживаемые теги

### `<font color='...'>...</font>`

Цветной текст. Доступные цвета:

| Цвет | ANSI |
|---|---|
| `black` | 30 |
| `red` | 31 |
| `green` | 32 |
| `yellow` | 33 |
| `blue` | 34 |
| `magenta` | 35 |
| `cyan` | 36 |
| `white` | 37 |

```php
CLIConsole::say("<font color='red'>Error!</font>");
CLIConsole::say("<font color='cyan'>Info</font>");
```

### `<strong>...</strong>`

Жирный текст (белый цвет).

```php
CLIConsole::say("<strong>Important</strong> message");
```

### `<br>` и `<br/>`

Перенос строки.

```php
CLIConsole::say("Line 1<br>Line 2<br/>Line 3");
```

### `<hr>`

Горизонтальная линия с настройкой цвета и ширины.

```php
CLIConsole::say("<hr>");                             // 80 символов, белый
CLIConsole::say("<hr color='red' width='40'/>");     // 40 символов, красный
CLIConsole::say("<hr width='20' color='cyan'/>");    // 20 символов, cyan (порядок атрибутов не важен)
```

## Опции класса

Настройки изменяются через `setOption()` или `setOptions()`:

```php
// Одна опция
CLIConsole::setOption('strip_tags', true);

// Несколько опций
CLIConsole::setOptions([
    'strip_tags' => true,
    'hr_length' => 100,
    'newline_before_hr' => false,
]);
```

### Доступные опции

| Опция | Тип | По умолчанию | Описание |
|---|---|---|---|
| `strip_tags` | bool | `false` | Вырезать все не-распознанные HTML-теги после обработки |
| `decode_entities` | bool | `false` | Декодировать HTML-сущности (`&amp;`, `&lt;` и т.д.) |
| `hr_length` | int | `80` | Длина `<hr>` по умолчанию |
| `no_say_mode` | bool | `false` | Отключить вывод (для тестов) |
| `newline_before_hr` | bool | `true` | Добавлять перенос строки перед `<hr>` |
| `newline_after_hr` | bool | `true` | Добавлять перенос строки после `<hr>` |

## Форматирование без вывода

### `format()`

Возвращает отформатированную строку без вывода в stdout:

```php
$formatted = CLIConsole::format(
    "<font color='green'>Success!</font>",
    break_line: false,  // не добавлять PHP_EOL в конце
);

// $formatted содержит ANSI escape-коды
```

### `get_message()`

Автоматически выбирает формат в зависимости от режима:

```php
$message = CLIConsole::get_message("<font color='red'>Error</font>");

// В CLI: ANSI escape-коды
// В Web: "Error" + <br/>\r\n (если break_line = true)
```

## Метод `say()`

Основной метод вывода.

### Простая строка

```php
CLIConsole::say("<font color='green'>Hello</font>");
CLIConsole::say("Message without newline", break_line: false);
```

### Sprintf-формат через массив

Если передать массив, он будет развёрнут в `sprintf(...)`:

```php
CLIConsole::say([
    "<font color='cyan'>User</font> %s logged in from %s",
    'john',
    '192.168.1.1'
]);
```

Эквивалентно:
```php
CLIConsole::say(sprintf(
    "<font color='cyan'>User</font> %s logged in from %s",
    'john',
    '192.168.1.1'
));
```

## Интерактивный ввод (`readline`)

Метод `readline()` блокирует выполнение до получения валидного ввода:

```php
$name = CLIConsole::readline("Enter your name: ");
// Пользователь вводит любое значение

$email = CLIConsole::readline(
    prompt: "Enter email: ",
    allowed_pattern: '/^[^@]+@[^@]+\.[^@]+$/',
    strict_mode: true
);
// Цикл повторяется, пока пользователь не введёт валидный email
```

### Параметры

| Параметр | Тип | По умолчанию | Описание |
|---|---|---|---|
| `prompt` | string | — | Текст приглашения |
| `allowed_pattern` | string | `'/.*/'` | Regex для валидации |
| `strict_mode` | bool | `false` | Требовать `/` в начале и конце паттерна |

### Режимы валидации

**Не-строгий режим** (по умолчанию) — автоматически добавляет `/`:

```php
// Паттерн '/^[a-z]+$/' применится как есть
// Паттерн '^[a-z]+$' превратится в '/^[a-z]+$/'
CLIConsole::readline("Letters only: ", '^[a-z]+$');
```

**Строгий режим** — требует `/` с обеих сторон, иначе вернёт `false`:

```php
$result = CLIConsole::readline(
    prompt: "Number: ",
    allowed_pattern: '/^\d+$/',
    strict_mode: true
);
// OK, вернёт введённое число

$result = CLIConsole::readline(
    prompt: "Number: ",
    allowed_pattern: '^\d+$',  // нет /
    strict_mode: true
);
// Вернёт false сразу, без запроса ввода
```

## Web-режим

При запуске через веб-сервер (не CLI) класс автоматически адаптирует вывод:

```php
CLIConsole::say("<font color='red'>Error</font>");

// В CLI: "\033[31mError\033[0m\n"
// В Web: "Error<br/>\r\n"
```

HTML-теги остаются как есть, `<br>` преобразуется в `<br/>\r\n`. Это позволяет использовать один и тот же код для CLI и веб-скриптов.

## Интеграция с CLIRunner

`CLIConsole` идеально дополняет `CLIRunner`:

```php
use Arris\Toolkit\CLIRunner;
use Arris\Toolkit\CLIOptionType;
use Arris\Toolkit\CLIConsole;

$cli = new CLIRunner("<font color='cyan'>My script</font>");

$cli->register(
    option: 'process',
    handler: function ($value, CLIRunner $runner) {
        CLIConsole::say("<font color='yellow'>Processing...</font>");
        // ... работа ...
        CLIConsole::say("<font color='green'>Done!</font>");
    },
);

$cli->run();
```

Для условного вывода в зависимости от `--verbose` используйте `$runner->say()`:

```php
$cli->register(
    option: 'process',
    handler: function ($value, CLIRunner $runner) {
        // Только с --verbose
        $runner->say("<font color='yellow'>Debug info</font>");
        
        // Всегда
        $runner->say("<font color='green'>Done!</font>", always: true);
    },
);
```

## API Reference

### Основные методы

| Метод | Описание |
|---|---|
| `say(string\|array $message = '', bool $break_line = true): void` | Вывод сообщения в stdout |
| `format(string $message = '', bool $break_line = true): string` | Форматирование без вывода |
| `get_message(string $message = '', bool $break_line = true): string` | Форматирование с автоопределением режима (CLI/Web) |

### Настройка

| Метод | Описание |
|---|---|
| `setOption(string $option, mixed $value): void` | Установить одну опцию |
| `setOptions(array $options = []): void` | Установить несколько опций |

### Интерактивный ввод

| Метод | Описание |
|---|---|
| `readline(string $prompt, string $allowed_pattern = '/.*/', bool $strict_mode = false): string\|false` | Запрос ввода с валидацией |

## Примеры использования

### Прогресс-бар с цветами

```php
$total = 100;
for ($i = 1; $i <= $total; $i++) {
    $percent = (int)(($i / $total) * 100);
    $color = match(true) {
        $percent < 30 => 'red',
        $percent < 70 => 'yellow',
        default => 'green',
    };
    
    CLIConsole::say([
        "\r<font color='%s'>Progress: %d%%</font>",
        $color,
        $percent
    ], break_line: false);
    
    usleep(50000);
}
CLIConsole::say(""); // перевод строки
```

### Цветной help с разделителями

```php
CLIConsole::say("<font color='cyan'>My Awesome Tool v1.0</font>");
CLIConsole::say("<hr color='cyan' width='40'/>");
CLIConsole::say("<strong>Usage:</strong> php script.php [options]");
CLIConsole::say("<br>");
CLIConsole::say("<font color='yellow'>-h, --help</font>      Show this help");
CLIConsole::say("<font color='yellow'>-v, --verbose</font>   Enable verbose output");
CLIConsole::say("<font color='yellow'>-c, --config</font>    Config file path");
CLIConsole::say("<hr color='cyan' width='40'/>");
```

### Интерактивный CLI-мастер

```php
CLIConsole::say("<font color='cyan'>Welcome to setup wizard</font>");
CLIConsole::say("<hr width='50'/>");

$name = CLIConsole::readline("Your name: ", '/^[A-Za-z\s]{2,50}$/');
CLIConsole::say("Hello, <strong>{$name}</strong>!");

$email = CLIConsole::readline(
    prompt: "Email: ",
    allowed_pattern: '/^[^@]+@[^@]+\.[^@]+$/',
);

$agree = CLIConsole::readline(
    prompt: "Accept terms? (y/n): ",
    allowed_pattern: '/^[yn]$/i',
);

CLIConsole::say("<br><font color='green'>Setup complete!</font>");
```

### Отладочный вывод без strip_tags

```php
// При разработке — показываем все теги
CLIConsole::setOption('strip_tags', false);
CLIConsole::say("<div class='test'>Raw HTML preserved</div>");

// В production — вырезаем лишнее
CLIConsole::setOption('strip_tags', true);
CLIConsole::say("<div class='test'>Only text remains</div>");
```

## Требования

- PHP 8.2+
- Модуль `readline` для интерактивного ввода (опционально)

## Лицензия

MIT

## Автор

Karel Wintersky — [github.com/KarelWintersky/Arris](https://github.com/KarelWintersky/Arris)