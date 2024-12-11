# Arris µ-Framework : CLIConsole

CLI Console class

# History

- ~2015y - first version
- 2018/03/04 - implementation as CLIConsole class
- 2019/04/24 - first commit at Arris Framework
- 2020/03/28 - implementation as part of Arris µFramework (separate class)
- 2020/10/06 - implementation as Arris.Toolkit.CLIConsole submodule
- 2023/08/07 - new colors
- 2024/07/22 - hr with custom width 
- 2024/12/11 - new CLIPrompt class, some fixes in call methods

# CLIConsole::say


# CLIPrompt 

```php

\Arris\Toolkit\CLIConsole::say('Say hello: ');

$answer = \Arris\Toolkit\CLIPrompt::hiddenPrompt();

\Arris\Toolkit\CLIConsole::say('You answered: '.$answer );
```

hiddenPrompt - Prompts the user for input and hides what they type. If this fails for any reason 
and $allowFallback is set to true the prompt will be done using the usual fgets() and characters will be visible.

prompt - Regular user prompt for input with characters being shown on screen.


# Интересные библиотеки на тему расцветки и работы с консолью

https://packagist.org/packages/aplus/cli - в целом
https://packagist.org/packages/mnapoli/silly - Silly CLI micro-framework based on Symfony Console.
https://packagist.org/packages/league/climate - PHP's best friend for the terminal. CLImate allows you to easily output colored text, special formats, and more.
    https://climate.thephpleague.com/

https://github.com/wp-cli/php-cli-tools - тут есть интересный Progress Bar

## Расцветка

https://packagist.org/packages/kevinlebrun/colors.php - интересный подход с темами

## Парсинг аргументов

https://packagist.org/packages/nategood/commando 

## Выполнение команд 

https://packagist.org/packages/adhocore/cli

## МЕНЮ

https://packagist.org/packages/php-school/cli-menu
