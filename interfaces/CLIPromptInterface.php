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
     * Prompts the user for input and hides what they type
     *
     * @param bool $allowFallback If prompting fails for any reason and this is set to true the prompt
     *                               will be done using the regular prompt() function, otherwise a
     *                               \RuntimeException is thrown.
     * @return string
     * @throws \RuntimeException on failure to prompt, unless $allowFallback is true
     */
    public static function hiddenPrompt(bool $allowFallback = false): string;


}