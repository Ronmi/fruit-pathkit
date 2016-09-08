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
    public function iterate($base = '')
    {
        $initLevel = 0;
        if ($this->isAbs) {
            // ignore $base
            $base = '/';
            $initLevel = 1;
        } else {
            if ($base === '') {
                $base = '.';
            }
            $base = (new Path($base, null, $this->separator))->normalize();
            if (!is_dir($base)) {
                $base = dirname($base);
            }
        }

        // array(data, level)
        // level is index of $this->parts
        $pending = array(array($base, $initLevel));
        $max = count($this->parts);

        while (($cur = array_pop($pending)) !== null) {
            list($path, $level) = $cur;
            if ($level >= $max) {
                yield $path;
                continue;
            }

            list($type, $data) = $this->parts[$level];

            switch ($type) {
                case self::DIR:
                    $dest = $path . $this->separator . $data;
                    if (file_exists($dest)) {
                        array_push($pending, array($dest, $level+1));
                    }
                    break;

                case self::DYN:
                    $regex = '/^' . $data . '$/';
                    if ($h = opendir($path)) {
                        while (false !== ($name = readdir($h))) {
                            if (1 !== preg_match($regex, $name)) {
                                continue;
                            }

                            $dest = $path . $this->separator . $name;
                            array_push($pending, array($dest, $level+1));
                        }
                        closedir($h);
                    }
                    break;

                case self::IGN:
                    break;
            }
        }
    }
}
