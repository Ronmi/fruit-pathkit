<?php

namespace FruitTest\PathKit;

use Fruit\PathKit\Path;

class PathWindowsTest extends \PHPUnit_Framework_TestCase
{
    public function expendWindowsP()
    {
        return array(
            array("C:\\qwe", "asd", "C:\\qwe\\asd"),
            array("C:\\qwe\\qwe", "asd\\asd", "C:\\qwe\\qwe\\asd\\asd"),
            array("C:\\", ".", "C:\\."),
            array("C:\\", "..", "C:\\.."),
            array("C:\\", "C:\\", "C:\\"),
        );
    }

    /**
     * @dataProvider expendWindowsP
     */
    public function testExpandWindows($base, $path, $expect)
    {
        $p = new Path($path, $base, "\\");
        $actual = $p->expand();
        $this->assertEquals($expect, $actual);
    }

    public function normalizeP()
    {
        return array(
            array("C:\\", ".", "C:\\"),
            array("C:\\", "..", "C:\\"),
            array("C:\\..", "..\\..", "C:\\"),
            array("C:\\b", "..\\..\\a", "C:\\a"),
            array("C:\\", "asd", "C:\\asd"),
            array("C:\\asd", "..", "C:\\"),
            array("C:\\a\\b\\c\\d\\", "..\\..\\..\\e\\f\\g\\", "C:\\a\\e\\f\\g"),
            array("C:\\a", "b", "C:\\a\\b"),
        );
    }

    /**
     * @dataProvider normalizeP
     */
    public function testNormalize($base, $path, $expect)
    {
        $p = new Path($path, $base, "\\");
        $actual = $p->normalize();
        $this->assertEquals($expect, $actual);
    }

    public function withinP()
    {
        return array(
            array("C:\\a", "C:\\a\\b\\c", true),
            array("C:\\b", "C:\\a\\b\\c", false),
            array("C:\\b", "C:\\a\\b", false),
            array("C:\\b", "..", false),
            array("C:\\a\\b\\..", "C:\\a\\c", true),
            array("C:\\a\\b", "c\\d\\..\\..\\e\\f", true),
            array("C:\\a\\b", "c\\d\\..\\..\\..\\e\\f", false),
            array("C:\\a", "D:\\a\\b\\c", false),
        );
    }

    /**
     * @dataProvider withinP
     */
    public function testWithin($base, $path, $expect)
    {
        $p = new Path($path, $base, "\\");
        $actual = $p->within();
        $this->assertEquals($expect, $actual);
    }

    public function relativeP()
    {
        return array(
            array("C:\\a", "C:\\a\\b\\c", "b\\c"),
            array("C:\\a\\b", "C:\\a\\c", "..\\c"),
            array("C:\\a", "C:\\d\\b\\c", "..\\d\\b\\c"),
            array("C:\\a", "D:\\a", "D:\\a"),
            array("C:\\a", ".", "."),
            array("C:\\a\\b", "..", ".."),
            array("C:\\", "..", "."),
            array("C:\\", "C:\\", "."),
            array("C:\\a\\b", "..", ".."),
            array("C:\\a", "..", ".."),
        );
    }

    /**
     * @dataProvider relativeP
     */
    public function testRelative($base, $path, $expect)
    {
        $p = new Path($path, $base, "\\");
        $actual = $p->relative();
        $this->assertEquals($expect, $actual);
    }

    public function absP()
    {
        return array(
            array("C:\\", true),
            array("C:\\\\", true),
            array("C:\\..", true),
            array("..", false),
            array(".", false),
            array("a", false),
            array("a\\..\\..\\..\\..\\..\\..\\..\\..\\..\\..\\..\\.\\..\\..\\..\\..\\", false),
            array("a\\..\\..\\..\\..\\..\\..\\..\\..\\..\\..\\..\\.\\..\\..\\..\\..\\\\\\\\\\\\", false),
        );
    }

    /**
     * @dataProvider absP
     */
    public function testAbsolute($path, $expect)
    {
        $p = new Path($path, "C:\\", "\\");
        $actual = $p->isAbsolute();
        $this->assertEquals($expect, $actual);
    }
}
