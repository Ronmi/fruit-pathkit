<?php

namespace Fruit\PathKit;

/**
 * This class helps you checking if path info is valid,
 * and convert between relative or absolute path.
 */
class Path
{
    private $path;
    private $base;
    private $expanded;
    private $normalized;
    private $separator;

    /**
     * Constructor
     *
     * @param $path string the path you want to validate or convert.
     * @param $base string (optional) base directory when converting
              relative path to absolute path. default to current working directory.
     * @param $separator string directory separator, defaults to DIRECTORY_SEPARATOR.
     */
    public function __construct(string $path, string $base = '', string $separator = '')
    {
        $this->path = $path;
        $this->base = $base;
        if ($separator === '') {
            $separator = DIRECTORY_SEPARATOR;
        }
        $this->separator = $separator;

        if ($base === '') {
            $base = getcwd();
        }
        $base = self::strip($base, $separator);
        $this->base = $base;

        $this->expanded = null;
        $this->normalized = null;
    }

    private static function strip(string $path, string $sep): string
    {
        if (substr($path, strlen($path) - 1) == $sep) {
            $path = substr($path, 0, strlen($path) - 1);
        }
        return $path;
    }

    private static function isAbs(string $path, string $sep): bool
    {
        list($drive) = explode($sep, $path);
        return ($drive === '' or (strlen($drive) === 2 and ctype_alpha($drive[0]) and $drive[1] === ':'));
    }

    /**
     * Test if this is an absolute path
     */
    public function isAbsolute(): bool
    {
        return self::isAbs($this->path, $this->separator);
    }

    private static function doExpand(string $path, string $base, string $sep): string
    {
        if (self::isAbs($path, $sep)) {
            // this is absolute path
            return $path;
        }
        $base = self::doExpand($base, getcwd(), $sep);
        return self::strip($base, $sep) . $sep . $path;
    }

    /**
     * Expands relative path to full path base on specified directory.
     *
     * @return Full path.
     */
    public function expand(): string
    {
        if ($this->expanded == null) {
            $this->expanded = self::doExpand($this->path, $this->base, $this->separator);
        }
        return $this->expanded;
    }

    private static function doNorm(string $path, string $base, string $sep): string
    {
        $abs = self::doExpand($path, $base, $sep);
        $arr = explode($sep, $abs);
        $ret = array($arr[0]); // drive letter when windows, null if other system
        $cur = 1;

        // process and strip the . and ..
        for ($i = 1; $i < count($arr); $i++) {
            $ele = $arr[$i];
            switch ($ele) {
                case '.':
                case '':
                    break;
                case '..':
                    $cur--;
                    if ($cur < 1) {
                        $cur = 1;
                    }
                    break;
                default:
                    $ret[$cur++] = $ele;
            }
        }

        $result = implode($sep, array_slice($ret, 0, $cur));
        if ($result == $ret[0]) {
            $result .= $sep;
        }

        return $result;
    }

    /**
     * Strip special elements (. and ..) in path.
     *
     * @return Full path.
     */
    public function normalize(): string
    {
        if ($this->normalized == null) {
            $this->normalized = self::doNorm($this->path, $this->base, $this->separator);
        }
        return $this->normalized;
    }

    /**
     * Check if the path lays within specified directory.
     *
     * @param string $base "Specified directory"
     * @return true or false.
     */
    public function within(string $base = ''): bool
    {
        if ($base === '') {
            $base = $this->base;
        }
        $path = $this->normalize($this->path);
        $base = (new self($base, '', $this->separator))->normalize();
        $ret = false;
        if (substr($path, 0, strlen($base)) == $base) {
            $ret = true;
        }
        return $ret;
    }

    /**
     * Get relative path.
     *
     * @param string $base Path of basement.
     * @return string of relative path from base to target.
     */
    public function relative(string $base = ''): string
    {
        if ($base === '') {
            $base = $this->base;
        }
        $norm = $this->normalize();
        $target = explode($this->separator, $norm);
        $base = explode($this->separator, self::doNorm($base, getcwd(), $this->separator));

        // if one is windows and another is unix, or they are in different
        // windows drive, then we have to return the absolute path.
        if ($target[0] != $base[0]) {
            return $this->normalize();
        }
        array_shift($target);
        array_shift($base);

        $min = min(count($target), count($base));

        for ($i = 0; $i < $min; $i++) {
            if ($target[$i] != $base[$i]) {
                break;
            }
        }

        if ($i > 0) {
            $target = array_slice($target, $i);
            $base = array_slice($base, $i);
        }

        if (count($target) + count($base) == 0) {
            return '.';
        }
        $ret = array();
        $base_size = count($base);
        if ($base_size > 0 && !($base_size ==1 && $base[0] === "")) {
            $ret = array_fill(0, count($base), '..');
        }
        if ($norm != self::strip($norm, $this->separator)) {
            // strip tailing slash
            array_shift($target);
        }
        $ret = array_merge($ret, $target);

        return implode($this->separator, $ret);
    }

    /**
     * Append some path after current path.
     *
     * @return self
     */
    public function join(string ...$path): self
    {
        foreach ($path as $p) {
            $this->path = rtrim($this->path, $this->separator);
            $this->path .= $this->separator . ltrim($p, $this->separator);
        }

        return $this;
    }

    public function __get(string $name)
    {
        switch ($name) {
            case 'path':
                return $this->path;
            case 'separator':
                return $this->separator;
            case 'base':
                return $this->base;
        }
    }

    /**
     * Get root path, `/` on unix, root of current working drive on windows.
     *
     * @return string of root path.
     */
    public static function root(): string
    {
        $sep = DIRECTORY_SEPARATOR;

        if ($sep === '/') {
            // quick solution for unix path
            return $sep;
        }

        $cwd = getcwd();
        list($root) = explode($sep, $cwd);
        return $root . $sep;
    }
}
