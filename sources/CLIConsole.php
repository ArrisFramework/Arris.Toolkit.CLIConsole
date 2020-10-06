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
    private static $options = [
        'strip_tags'        =>  false,
        'decode_entities'   =>  false,
        'hr_length'         =>  80,
        'no_say_mode'       =>  false,
        'newline_after_hr'  =>  true,
        'newline_before_hr' =>  true
    ];
    
    public static function setOptions($options = [])
    {
        foreach ($options as $key => $value) {
            if (array_key_exists($key, self::$options)) {
                self::$options[ $key ] = $value;
            }
        }
    }

    public static function readline($prompt, $allowed_pattern = '/.*/', $strict_mode = false)
    {
        if ($strict_mode) {
            if ((substr($allowed_pattern, 0, 1) !== '/') || (substr($allowed_pattern, -1, 1) !== '/')) {
                return FALSE;
            }
        } else {
            if (substr($allowed_pattern, 0, 1) !== '/')
                $allowed_pattern = '/' . $allowed_pattern;
            if (substr($allowed_pattern, -1, 1) !== '/')
                $allowed_pattern .= '/';
        }

        do {
            $result = readline($prompt);

        } while (preg_match($allowed_pattern, $result) !== 1);
        return $result;
    }

    public static function echo_status_cli($message = "", $linebreak = true)
    {
        $fg_colors = self::FOREGROUND_COLORS;

        // replace <br>
        $pattern_br = '#(?<br>\<br\s?\/?\>)#U';
        $message = preg_replace_callback($pattern_br, function ($matches) {
            return PHP_EOL;
        }, $message);

        // replace <hr>
        $pattern_hr = '#(?<hr>\<hr\s?\/?\>)#U';
        $message = preg_replace_callback($pattern_hr, function ($matches) {
            return
                (self::$options['newline_before_hr'] ? PHP_EOL : '') .
                str_repeat('-', (int)self::$options['hr_length']) .
                (self::$options['newline_after_hr'] ? PHP_EOL : '');
        }, $message);

        // replace <font>
        $pattern_font = '#(?<Full>\<font[\s]+color=[\\\'\"](?<Color>[\D]+)[\\\'\"]\>(?<Content>.*)\<\/font\>)#U';
        $message = preg_replace_callback($pattern_font, function ($matches) use ($fg_colors) {
            $color = isset($fg_colors[$matches['Color']]) ? $fg_colors[$matches['Color']] : $fg_colors['white'];
            return "\033[{$color}m{$matches['Content']}\033[0m";
        }, $message);

        // replace <strong>
        $pattern_strong = '#(?<Full>\<strong\>(?<Content>.*)\<\/strong\>)#U';
        $message = preg_replace_callback($pattern_strong, function ($matches) use ($fg_colors) {
            $color = $fg_colors['white'];
            return "\033[{$color}m{$matches['Content']}\033[0m";
        }, $message);

        // вырезает все лишние таги (если установлен флаг)
        if (self::$options['strip_tags']) {
            $message = strip_tags($message);
        }

        // преобразует html entity-сущности (если установлен флаг)
        if (self::$options['decode_entities']) {
            $message = htmlspecialchars_decode($message, ENT_QUOTES | ENT_HTML5);
        }

        if ($linebreak === true) {
            $message .= PHP_EOL;
        }
        
        if (self::$options['no_say_mode'] === false) {
            echo $message;
        }
        
        return $message;
    }

    public static function say($message = "", $linebreak = true)
    {
        if (php_sapi_name() === "cli") {
            return self::echo_status_cli($message, $linebreak);
        } else {
            if ($linebreak === true) $message .= PHP_EOL . "<br/>\r\n";
            echo $message;
        }
        return $message;
    }
    
}

# -eof-
