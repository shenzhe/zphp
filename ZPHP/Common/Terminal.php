<?php
namespace ZPHP\Common;

/**
 * Terminal
 *
 * @package Core
 *
 * @author Zorin Vasily <kak.serpom.po.yaitsam@gmail.com>
 */
class Terminal
{
    /*
    * color define
    */
    private static $color = array(
        'default' => 0,
        'black' => 30,
        'red' => 31,
        'green' => 32,
        'brown' => 33,
        'blue' => 34,
        'magenta' => 35,
        'cyan' => 36,
        'lightgray' => 37,
    );

    /**
     * Is color allowed in terminal?
     * @var boolean
     */
    public static $enable_color = true;

    /**
     * Maximum terminal width
     * @var int
     */
    private static $columns = 80;

    public static function drawStr($str, $color = 'default')
    {
        self::setStyle(isset(self::$color[$color]) ? self::$color[$color] : $color);
        echo $str;
        self::resetStyle();
    }

    /**
     * Read a line from STDIN
     * @return string Line
     */
    public static function readln()
    {
        return fgets(STDIN);
    }

    /**
     * Clear the terminal with CLR
     * @return void
     */
    public static function clearScreen()
    {
        echo "\x0c";
    }

    /**
     * Set text style
     * @param string Style
     * @return void
     */
    public static function setStyle($c)
    {
        if (self::$enable_color) {
            echo "\033[" . $c . 'm';
        }
    }

    /**
     * Reset style to default
     * @return void
     */
    public static function resetStyle()
    {
        if (self::$enable_color) {
            echo "\033[0m";
        }
    }

    /**
     * Counting terminal char width
     * @return int
     */
    private function getMaxColumns()
    {
        if (
            preg_match_all("/columns.([0-9]+);/", strtolower(@exec('stty -a | grep columns')), $output)
            && 2 == sizeof($output)
        ) {
            return $output[1][0];
        }

        return 80;
    }

    /**
     * Draw param (like in man)
     * @param string Param name
     * @param string Param description
     * @param array Param allowed values
     * @return void
     */
    public static function drawParam($name, $description, $values = '')
    {
        self::$columns = self::getMaxColumns();
        $paramw = round(self::$columns / 3);

        echo "\n";

        $leftcolumn = array();

        $valstr = is_array($values) ? implode('|', array_keys($values)) : $values;

        if ('' !== $valstr) {
            $valstr = '=[' . $valstr . ']';
        }

        $paramstr = "  \033[1m--" . $name . $valstr . "\033[0m";

        $pl = strlen($paramstr);
        if ($pl + 2 >= $paramw) {
            $paramw = $pl + 3;
        }

        $descw = self::$columns - $paramw;

        $leftcolumn[] = $paramstr;

        if (is_array($values)) {
            foreach ($values as $key => $value) {
                $leftcolumn[] = '    ' . $key . ' - ' . $value;
            }
        }

        if (strlen($description) <= $descw) {
            $rightcolumn[] = $description;
        } else {
            $m = explode(' ', $description);

            $descstr = '';

            while (sizeof($m) > 0) {
                $el = array_shift($m);

                if (strlen($descstr) + strlen($el) >= $descw) {
                    $rightcolumn[] = $descstr;
                    $descstr = '';
                } else {
                    $descstr .= ' ';
                }

                $descstr .= $el;
            }

            if ('' !== $descstr) {
                $rightcolumn[] = $descstr;
            }
        }

        while (
            sizeof($leftcolumn) > 0
            || sizeof($rightcolumn) > 0
        ) {
            if ($l = array_shift($leftcolumn)) {
                echo str_pad($l, $paramw, ' ');
            } else {
                echo str_repeat(' ', $paramw - 7);
            }

            if ($r = array_shift($rightcolumn)) {
                echo $r;
            }

            echo "\n";
        }
    }

    /**
     * Draw a table
     * @param array Array of table's rows.
     * @return void
     */
    public static function drawTable($rows)
    {
        $pad = array();

        foreach ($rows as $row) {
            foreach ($row as $k => $v) {
                if (substr($k, 0, 1) == '_') {
                    continue;
                }

                if (
                    !isset($pad[$k])
                    || (strlen($v) > $pad[$k])
                ) {
                    $pad[$k] = strlen($v);
                }
            }
        }

        foreach ($rows as $row) {
            if (isset($row['_color'])) {
                self::setStyle($row['_color']);
            }

            if (isset($row['_bold'])) {
                self::setStyle('1');
            }

            if (isset($row['_'])) {
                echo $row['_'];
            } else {
                $i = 0;

                foreach ($row as $k => $v) {
                    if (substr($k, 0, 1) == '_') {
                        continue;
                    }

                    if ($i > 0) {
                        echo "\t";
                    }

                    echo str_pad($v, $pad[$k]);
                    ++$i;
                }
            }

            self::resetStyle();
            echo "\n";
        }
    }
}
