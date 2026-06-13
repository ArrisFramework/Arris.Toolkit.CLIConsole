<?php

namespace Arris\Toolkit;

class CLIPrompt implements CLIPromptInterface
{
    /**
     * Кэш для обнаруженного shell
     */
    private static ?string $cachedShell = null;

    public static function readline(
        string $prompt,
        string $allowedPattern = '/.*/',
        bool $strictMode = false,
    ): bool|string
    {
        $pattern = self::normalizePattern($allowedPattern, $strictMode);

        if ($pattern === false) {
            return false;
        }

        do {
            $result = readline($prompt);
            if ($result === false) {
                return false; // EOF или ошибка
            }
        } while (preg_match($pattern, $result) !== 1);

        return $result;
    }

    public static function prompt(string $message = ''): string
    {
        if ($message !== '') {
            echo $message;
        }

        $stdin = fopen('php://stdin', 'r');
        if (false === $stdin) {
            throw new \RuntimeException('Failed to open STDIN, could not prompt user for input.');
        }
        $answer = fgets($stdin, 4096);
        fclose($stdin);

        return self::trimAnswer($answer ?: '');
    }

    public static function hiddenPrompt(string $message = 'Password: ', bool $allowFallback = false): string
    {
        if (file_exists('/usr/bin/env')) {
            $shell = self::detectShell();

            if ($shell !== null) {
                $readCmd = match($shell) {
                    'csh' => 'set mypassword = $<',
                    default => 'read -r mypassword',
                };

                $command = sprintf(
                    "/usr/bin/env %s -c 'stty -echo; %s; stty echo; echo \$mypassword'",
                    $shell,
                    $readCmd
                );

                if ($message !== '') {
                    echo $message;
                }

                $output = shell_exec($command);

                if ($output !== null) {
                    echo PHP_EOL;
                    return self::trimAnswer($output);
                }
            }
        }

        // not able to hide the answer
        if (!$allowFallback) {
            throw new \RuntimeException('Could not prompt for input in a secure fashion, aborting');
        }

        return self::prompt();
    }

    /**
     * @param string|bool $str
     * @return string
     */
    private static function trimAnswer($str): string
    {
        return preg_replace('{\r?\n$}D', '', (string) $str) ?: '';
    }

    /**
     * Нормализация и валидация regex-паттерна
     */
    private static function normalizePattern(string $pattern, bool $strictMode): string|false
    {
        if ($strictMode) {
            if (!str_starts_with($pattern, '/') || !str_ends_with($pattern, '/')) {
                return false;
            }
        } else {
            // Non-strict mode: добавляем / если нужно
            if (!str_starts_with($pattern, '/')) {
                $pattern = '/' . $pattern;
            }
            if (!str_ends_with($pattern, '/')) {
                $pattern .= '/';
            }
        }

        // Проверка валидности regex
        if (@preg_match($pattern, '') === false) {
            return false;
        }

        return $pattern;
    }

    /**
     * Обнаружение доступного shell (с кэшированием)
     */
    private static function detectShell(): ?string
    {
        if (self::$cachedShell !== null) {
            return self::$cachedShell;
        }

        $test = "/usr/bin/env %s -c 'echo OK' 2> /dev/null";
        foreach (['bash', 'zsh', 'ksh', 'csh', 'sh'] as $sh) {
            $output = shell_exec(sprintf($test, $sh));
            if (is_string($output) && 'OK' === rtrim($output)) {
                self::$cachedShell = $sh;
                return $sh;
            }
        }

        return null;
    }
}
