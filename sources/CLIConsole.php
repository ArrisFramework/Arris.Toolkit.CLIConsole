<?php
/**
 * User: Karel Wintersky
 *
 * Class CLIConsole
 * Namespace: Arris
 *
 * Library: https://github.com/KarelWintersky/Arris
 *
 * Date: 04.03.2018, time: 19:27
 * Date: 18.07.2024, time: 15:30 - PHP8 compatible
 * Date: 14.06.2026, time: 01:30 - PHP8.2 required
 */

namespace Arris\Toolkit;

/**
 * Class CLIConsole
 */
class CLIConsole implements CLIConsoleInterface
{
    private const PATTERN_BR = '#(?<br>\<br\s?\/?\>)#U';
    private const PATTERN_HR = '#<\s*hr\s*(?:(?<attrName1>\w+)=[\\\"\'](?<attrValue1>\S*)[\\\"\'])?\s*(?:(?<attrName2>\w+)=[\\\"\'](?<attrValue2>\S*)[\\\"\'])>?#';
    private const PATTERN_FONT = '#(?<Full>\<font[\s]+color=[\\\'\"](?<Color>[\D]+)[\\\'\"]\>(?<Content>.*)\<\/font\>)#U';
    private const PATTERN_STRONG = '#(?<Full>\<strong\>(?<Content>.*)\<\/strong\>)#U';

    /**
     * @var array
     */
    private static array $options = [
        'strip_tags'        =>  false,
        'decode_entities'   =>  false,
        'hr_length'         =>  80,
        'no_say_mode'       =>  false,
        'newline_after_hr'  =>  true,
        'newline_before_hr' =>  true
    ];

    public static function setOption($option, $value):void
    {
        if (array_key_exists($option, self::$options)) {
            self::$options[ $option ] = $value;
        }
    }
    
    public static function setOptions(array $options = []): void
    {
        foreach ($options as $key => $value) {
            if (\array_key_exists($key, self::$options)) {
                self::$options[ $key ] = $value;
            }
        }
    }

    public static function format($message = "", bool $break_line = true): array|string|null
    {
        $fg_colors = self::FOREGROUND_COLORS;

        // replace <br>
        $message = \preg_replace_callback(self::PATTERN_BR, function ($matches) {
            return PHP_EOL;
        }, $message);

        // replace <hr>
        $pattern_hr = self::PATTERN_HR;
        $message = \preg_replace_callback($pattern_hr, function ($matches) {
            $attrName1  = $matches['attrName1'] ?? null;
            $attrName2  = $matches['attrName2'] ?? null;
            $attrValue1 = $matches['attrValue1'] ?? null;
            $attrValue2 = $matches['attrValue2'] ?? null;

            $color = $attrName1 === 'color' ? $attrValue1 : ($attrName2 === 'color' ? $attrValue2 : 'white');
            $width = $attrName1 === 'width' ? $attrValue1 : ($attrName2 === 'width' ? $attrValue2 : 80);
            $width = \max((int)$width, 0);
            $line = \str_repeat('-', $width);
            $color = self::FOREGROUND_COLORS[$color] ?? self::FOREGROUND_COLORS['white'];

            return
                (self::$options['newline_before_hr'] ? PHP_EOL : '') .
                "\033[{$color}m{$line}\033[0m" .
                (self::$options['newline_after_hr'] ? PHP_EOL : '');
        }, $message);

        // replace <font>
        $pattern_font = self::PATTERN_FONT;
        $message = \preg_replace_callback($pattern_font, function ($matches) use ($fg_colors) {
            $color = $fg_colors[$matches['Color']] ?? $fg_colors['white'];
            return "\033[{$color}m{$matches['Content']}\033[0m";
        }, $message);

        // replace <strong>
        $pattern_strong = self::PATTERN_STRONG;
        $message = \preg_replace_callback($pattern_strong, function ($matches) use ($fg_colors) {
            $color = $fg_colors['white'];
            return "\033[{$color}m{$matches['Content']}\033[0m";
        }, $message);

        // вырезает все лишние таги (если установлен флаг)
        if (self::$options['strip_tags']) {
            $message = \strip_tags($message);
        }

        // преобразует html entity-сущности (если установлен флаг)
        if (self::$options['decode_entities']) {
            $message = \htmlspecialchars_decode($message, ENT_QUOTES | ENT_HTML5);
        }

        if ($break_line === true) {
            $message .= PHP_EOL;
        }

        return $message;
    }


    public static function get_message(string $message = "", bool $break_line = true): array|string|null
    {
        if (php_sapi_name() === "cli") {
            $message = self::format($message, $break_line);
        } else {
            $message .= $break_line === true ? PHP_EOL . "<br/>\r\n" : '';
        }
        return $message;
    }

    public static function say(array|string $message = '', $break_line = true): void
    {
        if (empty($message)) {
            $message = '';
        }

        if (is_array($message) && !empty($message)) {
            $message = sprintf(...$message);
        }

        echo self::get_message($message, $break_line);
    }
    
}

# -eof- #
