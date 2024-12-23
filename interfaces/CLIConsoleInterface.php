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
     * Никогда и нигде не используется...
     *
     * @param $prompt -
     * @param string $allowed_pattern
     * @param bool $strict_mode
     * @return bool|string
     */
    public static function readline(string $prompt, string $allowed_pattern = '/.*/', bool $strict_mode = false);


    /**
     * Устанавливает один из флагов из списка ниже: <br>
     * - 'strip_tags' - вырезать ли все лишние теги после обработки заменяемых? (false)<br>
     *  - 'decode_entities' - преобразовывать ли html entities в их html-представление? (false)<br>
     *  - 'hr_length' - HR tag length (80)<br>
     *  - 'no_say_mode' - (false) если true - не печатать вывод на экран, а только вернуть<br>
     *  - 'newline_before_hr' (true) добавлять ли перевод строки перед HR<br>
     *  - 'newline_after_hr' (true) добавлять ли перевод строки после HR<br>
     *
     * @param $option
     * @param $value
     * @return mixed
     */
    public static function setOption($option, $value);

    /**
     * Устанавливает флаги обработки разных тегов в функции echo_status()
     * @param array $options <br>
     *  - 'strip_tags' - вырезать ли все лишние теги после обработки заменяемых? (false)<br>
     *  - 'decode_entities' - преобразовывать ли html entities в их html-представление? (false)<br>
     *  - 'hr_length' - HR tag length (80)<br>
     *  - 'no_say_mode' - (false) если true - не печатать вывод на экран, а только вернуть<br>
     * - 'newline_before_hr' (true) добавлять ли перевод строки перед HR<br>
     * - 'newline_after_hr' (true) добавлять ли перевод строки после HR<br>
     */
    public static function setOptions(array $options = []);

    /**
     * Печатает в консоли цветное сообщение. Рекомендуемый к использованию метод.
     *
     * Допустимые форматтеры:
     *
     * <font color=""> задает цвет из списка: black, dark gray, blue, light blue, green, lightgreen, cyan, light cyan, red, light red, purple, light purple, brown, yellow, light gray, gray
     * <hr> - горизонтальная черта, 80 минусов (работает только в отдельной строчке)
     * <strong> - заменяет белым цветом
     *
     * Отсутствие аргументов считается как пустая строка.
     *
     * @param string|array $message - строка или массив (в этом случае первый элемент - строка для sprintf)
     * @param bool $break_line - переводить ли строку
     */
    public static function say($message, bool $break_line = true);

    /**
     * Internal implementation. Форматирует сообщение и возвращает его.
     *
     * @param string $message
     * @param bool $break_line
     */
    public static function format(string $message = "", bool $break_line = true);

    /**
     * Генерирует сообщение - отформатированное ESCAPE-последовательностями для CLI
     * и не отформатированное (с тегами) для WEB
     *
     * @param string $message
     * @param bool $break_line
     * @return array|string|string[]|null
     */
    public static function get_message(string $message = "", bool $break_line = true);
}