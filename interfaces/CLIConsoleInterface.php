<?php

namespace Arris\Toolkit;

interface CLIConsoleInterface {
    
    const OPTION_DEFAULTS = [
        'strip_tags'        =>  false,
        'decode_entities'   =>  false,
        'hr_length'         =>  80,
        'no_say_mode'       =>  false,
        'newline_after_hr'  =>  true,
        'newline_before_hr' =>  true
    ];
    
    const FOREGROUND_COLORS = [
        'black'         => '0;30',
        'dark gray'     => '1;30',
        'dgray'         => '1;30',
        'blue'          => '0;34',
        'light blue'    => '1;34',
        'lblue'         => '1;34',
        'green'         => '0;32',
        'light green'   => '1;32',
        'lgreen'        => '1;32',
        'cyan'          => '0;36',
        'light cyan'    => '1;36',
        'lcyan'         => '1;36',
        'red'           => '0;31',
        'light red'     => '1;31',
        'lred'          => '1;31',
        'purple'        => '0;35',
        'light purple'  => '1;35',
        'lpurple'       => '1;35',
        'brown'         => '0;33',
        'yellow'        => '1;33',
        'light gray'    => '0;37',
        'lgray'         => '0;37',
        'white'         => '1;37'
    ];
    
    const BACKGROUND_COLORS = [
        'black'     => '40',
        'red'       => '41',
        'green'     => '42',
        'yellow'    => '43',
        'blue'      => '44',
        'magenta'   => '45',
        'cyan'      => '46',
        'light gray'=> '47'
    ];

    /**
     * CLIConsole::readline('Введите число от 1 до 999: ', '/^\d{1,3}$/');
     * CLIConsole::readline('Введите число от 100 до 999: ', '/^\d{3}$/');
     *
     * @param $prompt -
     * @param $allowed_pattern
     * @param bool|FALSE $strict_mode
     * @return bool|string
     */
    public static function readline($prompt, $allowed_pattern = '/.*/', $strict_mode = FALSE);

    /**
     * Устанавливает флаги обработки разных тегов в функции echo_status()
     * @param array $options <br>
     *  - 'strip_tags' - вырезать ли все лишние теги после обработки заменяемых? (false)<br>
     *  - 'decode_entities' - преобразовывать ли html entities в их html-представление? (false)<br>
     *  - 'hr_length' - HR tag length (80)<br>
     *  - 'no_say_mode' - (false) если true - не печатать вывод на экран, а только вернуть<br>
     *  - 'newline_after_hr' (true) добавлять ли перевод строки после HR<br>
     */
    public static function setOptions($options = []);

    /**
     * Печатает в консоли цветное сообщение. Рекомендуемый к использованию метод.
     *
     * Допустимые форматтеры:
     *
     * <font color=""> задает цвет из списка: black, dark gray, blue, light blue, green, lightgreen, cyan, light cyan, red, light red, purple, light purple, brown, yellow, light gray, gray
     * <hr> - горизонтальная черта, 80 минусов (работает только в отдельной строчке)
     * <strong> - заменяет белым цветом
     *
     * @param string $message
     * @param bool|TRUE $linebreak
     */
    public static function say($message = "", $linebreak = TRUE);

    /**
     * Internal implementation of SAY for CLI
     * Выводит сообщение на экран. Если мы вызваны из командной строки - заменяет теги на управляющие последовательности.
     *
     * @param $message
     * @param bool|TRUE $breakline
     */
    public static function echo_status_cli($message = "", $breakline = TRUE);
}