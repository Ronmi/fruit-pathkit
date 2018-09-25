<?php

namespace Fruit\PathKit;

use Iterator;

///
/// An easy to use globbing system.
///
/// Basically, the pattern is composed by `/`, `*`, `**` and names.
/// For example:
///
///   // matches this files
///   foo/bar.json
///   // matches all .json files in current directory
///   *.json
///   // matches all xxxTest.php files in child directory, not recursive
///   */*Test.php
///   // same as above, but recursive
///   test/**/*Test.php
///   // recursively match all children
///   test/**
///
/// The pattern parser has pretty good fault tolerance:
///
/// 1. `**`/`**`/`a` will be `**`/`a`
/// 2. No absolute pattern, `/a/b` will be `a/b`.
/// 3. Cannot use `**` with other patterns, so `a`/`b**`/`c` will be `a`/`**`/`c`
/// 4. For security reason, you cannot glob upper directory. `../a` will be `a`, `a/b/../c` will be `a/c`
///
class Glob
{
    /// constants
    ///
    const DIR = 0; // static part
    const DYN = 1; // xxx*xxx part
    const IGN = 2; // ** part

    // parsed elements
    // each element is an array(type, data)
    private $parts;
    private $separator;

    public function __construct(string $pattern, string $separator = '')
    {
        if ($separator === '') {
            $separator = DIRECTORY_SEPARATOR;
        }
        $this->separator = $separator;
        $this->parts = self::parse($pattern, $separator);
    }

    private static function parse(string $pattern, string $sep): array
    {
        $pattern = preg_replace('/\*{2,}/', '**', $pattern);
        $pattern = substr((new Path($pattern, DIRECTORY_SEPARATOR))->normalize(), 1);
        $arr = explode('/', $pattern);
        if ((new Path($pattern, '', $sep))->isAbsolute()) {
            array_shift($arr);
        }

        $tmp = array_map(
            function ($dir) use ($sep) {
                if (strpos($dir, '**') !== false) {
                    return array(self::IGN, '**');
                }
                if (strpos($dir, '*') === false) {
                    return array(self::DIR, $dir);
                }

                $regexp = self::pathToRegexp($dir, $sep);
                $regexp = str_replace('*', "[^\\" . $sep . ']*', $regexp);
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
     * Converts path to regexp (without prefix/postfix slash)
     *
     * CAUTION: asterisk is not converted.
     *
     * @return string
     */
    public static function pathToRegexp(string $path, string $separator): string
    {
        $ret = $path;
        if ($separator != "\\") {
            $ret = str_replace("\\", "\\\\", $ret);
        }
        $ret = str_replace($separator, "\\".$separator, $ret);
        $ret = str_replace('.', '\.', $ret);
        $ret = str_replace('+', '\+', $ret);
        $ret = str_replace('?', '\?', $ret);
        $ret = str_replace('^', '\^', $ret);
        $ret = str_replace('|', '\|', $ret);
        $ret = str_replace('(', '\(', $ret);
        $ret = str_replace(')', '\)', $ret);
        $ret = str_replace('[', '\[', $ret);
        $ret = str_replace(']', '\]', $ret);
        $ret = str_replace('{', '\{', $ret);
        $ret = str_replace('}', '\}', $ret);
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
    public function regex(string $base = ''): string
    {
        $ret = '/(^|\/)';
        if ($base != '') {
            $ret = '/^' . self::pathToRegexp($base, $this->separator);
            if (substr($ret, strlen($ret)-1) !== '/') {
                $ret .= '\/';
            }
        }

        $max = count($this->parts);
        foreach ($this->parts as $idx => $e) {
            switch ($e[0]) {
                case self::DIR:
                    $tmp = self::pathToRegexp($e[1], $this->separator);
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
    public function iterate(string $base = ''): Iterator
    {
        if ($base === '') {
            $base = '.';
        }
        $base = (new Path($base, '', $this->separator))->normalize();
        if (!is_dir($base)) {
            $base = dirname($base);
        }

        // array(data, level)
        // level is index of $this->parts
        $pending = array(array($base, 0));
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
