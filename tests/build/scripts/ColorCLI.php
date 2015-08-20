<?php

/**
 * PHP CLI Colors â€“ PHP Class Command Line Colors (bash)
 *
 * $str = "This is an example ";
 *
 * foreach (ColorCLI::$foregroundColors as $fg => $fgCode) {
 *     echo ColorCLI::$fg($str);
 *
 *     foreach (ColorCLI::$backgroundColors as $bg => $bgCode) {
 *         echo ColorCLI::$fg($str, $bg);
 *     }
 *
 *     echo PHP_EOL;
 * }
 *
 * @see http://www.if-not-true-then-false.com/2010/php-class-for-coloring-php-command-line-cli-scripts-output-php-output-colorizing-using-bash-shell-colors/
 */
class ColorCLI
{
    public static $foregroundColors = array(
        'bold'         => '1',    'dim'         => '2',
        'black'        => '0;30', 'dark_gray'   => '1;30',
        'blue'         => '0;34', 'lightBlue'   => '1;34',
        'green'        => '0;32', 'lightGreen'  => '1;32',
        'cyan'         => '0;36', 'lightCyan'   => '1;36',
        'red'          => '0;31', 'lightRed'    => '1;31',
        'purple'       => '0;35', 'lightPurple' => '1;35',
        'brown'        => '0;33', 'yellow'      => '1;33',
        'lightGray'    => '0;37', 'white'       => '1;37',
        'normal'       => '0;39',
    );

    public static $backgroundColors = array(
        'black'        => '40',   'red'         => '41',
        'green'        => '42',   'yellow'      => '43',
        'blue'         => '44',   'magenta'     => '45',
        'cyan'         => '46',   'lightGray'   => '47',
    );

    public static $options = array(
        'underline'    => '4',    'blink'       => '5',
        'reverse'      => '7',    'hidden'      => '8',
    );

    public static function __callStatic($foregroundColor, array $args)
    {
        if (!isset($args[0])) {
            throw new \InvalidArgumentException('Coloring string must be specified.');
        }

        $string        = $args[0];
        $coloredString = "";

        // Check if given foreground color found
        if (isset(static::$foregroundColors[$foregroundColor])) {
            $coloredString .= static::color(static::$foregroundColors[$foregroundColor]);
        } else {
            die($foregroundColor . ' not a valid color');
        }

        array_shift($args);

        foreach ($args as $option) {
            // Check if given background color found
            if (isset(static::$backgroundColors[$option])) {
                $coloredString .= static::color(static::$backgroundColors[$option]);
            } elseif (isset(self::$options[$option])) {
                $coloredString .= static::color(static::$options[$option]);
            }
        }

        // Add string and end coloring
        $coloredString .= $string . "\033[0m";

        return $coloredString;
    }

    public static function bell($count = 1)
    {
        echo str_repeat("\007", $count);
    }

    protected static function color($color)
    {
        return "\033[" . $color . "m";
    }
}
