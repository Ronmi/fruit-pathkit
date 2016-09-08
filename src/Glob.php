<?php

namespace Fruit\PathKit;

/// An easy to use globbing system.
///
/// Basically, the pattern is composed by `/`, `*`, `**` and names.
/// For example:
///
/// > // matches this files
/// > foo/bar.json
/// > // matches all .json files in current directory
/// > *.json
/// > // matches all xxxTest.php files in child directory, not recursive
/// > */*Test.php
/// > // same as above, but recursive
/// > test/**/*Test.php
/// > // recursively match all children
/// > test/**
///
/// The pattern parser has pretty good fault tolerance:
///
/// 1. No absolute pattern, `/a/b` will be `a/b`.
/// 2. `**/**/a` will be `**/a`
/// 3. Cannot use `**` with other patterns, so `a/b**/c` will be `a/**/c`
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

        $tmp = array_map(
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

        $ret = array($tmp[0]);
        for ($cnt = 1; $cnt < count($tmp); $cnt++) {
            $v = $tmp[$cnt];
            if ($v[1] === '**' and $ret[count($ret)-1][1] === '**') {
                continue;
            }
            array_push($ret, $v);
        }
        return $ret;
    }

    /**
     * Compiles globbing pattern to regular expression.
     *
     * We provide this method if you're not going to match globbing pattern
     * against real filesystem, or just want to gain control of your program's
     * flow.
     *
     * This method is also used in test codes of PathKit.
     *
     * @return string
     */
    public function regex()
    {
        $ret = '/';

        if ($this->isAbs) {
            // absolute pattern
            $ret .= '^';
        }

        $max = count($this->parts);
        foreach ($this->parts as $idx => $e) {
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
                    $dest = '(.+\/)?';

                    if ($idx === $max-1) {
                        // special treatment for ** as last element
                        $dest = '.+';
                    }

                    $ret .= $dest;
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
     * Recursive iterates through $base, returns every files and directories
     * matching this globbing pattern.
     *
     * This method is a generator.
     *
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
                    if (is_dir($path) and $h = opendir($path)) {
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
                    if ($level < $max-1) {
                        // match against next level
                        array_push($pending, array($path, $level+1));
                    }

                    // iterate every child, examine them with this level
                    if (is_dir($path) and $h = opendir($path)) {
                        while (false !== ($name = readdir($h))) {
                            // skip . and ..
                            if ($name === '.' or $name === '..') {
                                continue;
                            }
                            $dest = $path . $this->separator . $name;
                            array_push($pending, array($dest, $level));

                            // if ** is last level, all children are matched
                            if ($level === $max-1) {
                                yield $dest;
                            }
                        }
                        closedir($h);
                    }
                    break;
            }
        }
    }
}
