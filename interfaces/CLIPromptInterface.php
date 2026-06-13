<?php

namespace Arris\Toolkit;

interface CLIPromptInterface
{
    /**
     * Prompts the user for input and shows what they type
     *
     * @return string
     */
    public static function prompt(): string;


    /**
     * Скрытый запрос ввода (для паролей)
     *
     * @param string $message Сообщение перед запросом
     * @param bool $allowFallback Разрешить fallback к обычному prompt
     * @return string Введённая строка
     * @throws \RuntimeException Если скрытый ввод невозможен и fallback запрещён
     */
    public static function hiddenPrompt(string $message = 'Password: ', bool $allowFallback = false): string;

    /**
     * CLIConsole::readline('Введите число от 1 до 999: ', '/^\d{1,3}$/');
     * CLIConsole::readline('Введите число от 100 до 999: ', '/^\d{3}$/');
     *
     * Никогда и нигде не используется...
     *
     * @param $prompt -
     * @param string $allowedPattern
     * @param bool $strictMode
     * @return bool|string
     */
    public static function readline(string $prompt, string $allowedPattern = '/.*/', bool $strictMode = false);



}