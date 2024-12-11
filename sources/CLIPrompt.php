<?php

namespace Arris\Toolkit;

class CLIPrompt implements CLIPromptInterface
{
    public static function prompt(): string
    {
        $stdin = fopen('php://stdin', 'r');
        if (false === $stdin) {
            throw new \RuntimeException('Failed to open STDIN, could not prompt user for input.');
        }
        $answer = self::trimAnswer(fgets($stdin, 4096));
        fclose($stdin);

        return $answer;
    }

    public static function hiddenPrompt(bool $allowFallback = false): string
    {
        if (file_exists('/usr/bin/env')) {
            // handle other OSs with bash/zsh/ksh/csh if available to hide the answer
            $test = "/usr/bin/env %s -c 'echo OK' 2> /dev/null";
            foreach (array('bash', 'zsh', 'ksh', 'csh', 'sh') as $sh) {
                $output = shell_exec(sprintf($test, $sh));
                if (is_string($output) && 'OK' === rtrim($output)) {
                    $shell = $sh;
                    break;
                }
            }

            if (isset($shell)) {
                $readCmd = ($shell === 'csh') ? 'set mypassword = $<' : 'read -r mypassword';
                $command = sprintf("/usr/bin/env %s -c 'stty -echo; %s; stty echo; echo \$mypassword'", $shell, $readCmd);
                $output = shell_exec($command);

                if ($output !== null) {
                    echo PHP_EOL; // output a newline to be on par with the regular prompt()
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
}
