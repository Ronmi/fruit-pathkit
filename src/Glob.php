<?php

namespace Fruit\PathKit;

/**
 */
class Glob
{
    const DIR = 0; // static part
    const DYN = 1; // xxx*xxx part
    const IGN = 2; // ** part

    // parsed elements
    // each element is an array(type, data)
    private $parts;
    private $isAbs;
    private $separator;

    public function __construct($pattern, $separator = null)
    {
        if ($separator == '') {
            $separator = DIRECTORY_SEPARATOR;
        }
        $this->separator = $separator;
        $this->parts = self::parse($pattern, $separator);
        $this->isAbs = ($this->parts[0][0] === self::DIR and $this->parts[0][1] === '');
    }

    private static function parse($pattern, $sep)
    {
        $pattern = preg_replace('/\*{2,}/', '**', $pattern);
        $arr = explode('/', $pattern);

        return array_map(
            function ($dir) use ($sep) {
                if (strpos($dir, '**') !== false) {
                    return array(self::IGN, '**');
                }
                if (strpos($dir, '*') === false) {
                    return array(self::DIR, $dir);
                }

                $regexp = str_replace('*', "[^\\" . $sep . ']*', $dir);
                $regexp = str_replace('.', '\.', $regexp);
                return array(self::DYN, $regexp);
            },
            $arr
        );
    }

    /**
     * @return string
     */
    public function regex()
    {
        $ret = '/';

        if ($this->isAbs) {
            // absolute pattern
            $ret .= '^';
        }

        foreach ($this->parts as $e) {
            switch ($e[0]) {
                case self::DIR:
                    $tmp = str_replace('.', '\.', $e[1]);
                    $tmp = str_replace('$', '\$', $tmp);
                    $ret .= $tmp . '\/';
                    break;
                case self::DYN:
                    $ret .= $e[1] . '\/';
                    break;
                case self::IGN:
                    $ret .= '(.+\/)?';
                    break;
            }
        }

        $len = strlen($ret);
        if (substr($ret, $len-2) == '\/') {
            $ret = substr($ret, 0, $len-2);
        }
        return $ret . '$/';
    }

    /**
     * @return Iterator
     */
    public function iterate($path = '')
    {
    }
}
