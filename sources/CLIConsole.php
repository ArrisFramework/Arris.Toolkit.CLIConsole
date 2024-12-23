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
 */

namespace Arris\Toolkit;

/**
 * Class CLIConsole
 */
class CLIConsole implements CLIConsoleInterface
{
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

    public static function setOption($option, $value)
    {
        if (array_key_exists($option, self::$options)) {
            self::$options[ $option ] = $value;
        }
    }
    
    public static function setOptions(array $options = [])
    {
        foreach ($options as $key => $value) {
            if (\array_key_exists($key, self::$options)) {
                self::$options[ $key ] = $value;
            }
        }
    }

    public static function readline(string $prompt, string $allowed_pattern = '/.*/', bool $strict_mode = false)
    {
        if ($strict_mode) {
            if ((\substr($allowed_pattern, 0, 1) !== '/') || (\substr($allowed_pattern, -1, 1) !== '/')) {
                return false;
            }
        } else {
            if (\substr($allowed_pattern, 0, 1) !== '/')
                $allowed_pattern = '/' . $allowed_pattern;
            if (\substr($allowed_pattern, -1, 1) !== '/')
                $allowed_pattern .= '/';
        }

        do {
            $result = \readline($prompt);

        } while (\preg_match($allowed_pattern, $result) !== 1);

        return $result;
    }

    public static function format($message = "", bool $break_line = true)
    {
        $fg_colors = self::FOREGROUND_COLORS;

        // replace <br>
        $pattern_br = '#(?<br>\<br\s?\/?\>)#U';
        $message = \preg_replace_callback($pattern_br, function ($matches) {
            return PHP_EOL;
        }, $message);

        // replace <hr>
        $pattern_hr = '#<\s*hr\s*(?:(?<attrName1>\w+)=[\\\"\'](?<attrValue1>\S*)[\\\"\'])?\s*(?:(?<attrName2>\w+)=[\\\"\'](?<attrValue2>\S*)[\\\"\'])>?#';
        $message = \preg_replace_callback($pattern_hr, function ($matches) {
            $color = $matches['attrName1'] == 'color' ? $matches['attrValue1'] : ($matches['attrName2'] == 'color' ? $matches['attrValue2'] : "white");
            $width = $matches['attrName1'] == 'width' ? $matches['attrValue1'] : ($matches['attrName2'] == 'width' ? $matches['attrValue2'] : 80);
            $width = \max((int)$width, 0);
            $line = \str_repeat('-', $width);
            $color = self::FOREGROUND_COLORS[$color] ?? self::FOREGROUND_COLORS['white'];
            return
                (self::$options['newline_before_hr'] ? PHP_EOL : '') .
                "\033[{$color}m{$line}\033[0m" .
                (self::$options['newline_after_hr'] ? PHP_EOL : '');
        }, $message);

        // replace <font>
        $pattern_font = '#(?<Full>\<font[\s]+color=[\\\'\"](?<Color>[\D]+)[\\\'\"]\>(?<Content>.*)\<\/font\>)#U';
        $message = \preg_replace_callback($pattern_font, function ($matches) use ($fg_colors) {
            $color = $fg_colors[$matches['Color']] ?? $fg_colors['white'];
            return "\033[{$color}m{$matches['Content']}\033[0m";
        }, $message);

        // replace <strong>
        $pattern_strong = '#(?<Full>\<strong\>(?<Content>.*)\<\/strong\>)#U';
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
        
        /*if (self::$options['no_say_mode'] === false) {
            echo $message;
        }*/
        
        return $message;
    }


    public static function get_message(string $message = "", bool $break_line = true)
    {
        if (php_sapi_name() === "cli") {
            $message = self::format($message, $break_line);
        } else {
            $message .= $break_line === true ? PHP_EOL . "<br/>\r\n" : '';
        }
        return $message;
    }

    public static function say($message = '', $break_line = true)
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

# -eof-
