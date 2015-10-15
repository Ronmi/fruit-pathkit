<?php

namespace FruitTest\PathKit;

use Fruit\PathKit\Path;

class PathUnixTest extends \PHPUnit_Framework_TestCase
{
    public function expendP()
    {
        return array(
            array("/qwe", "asd", "/qwe/asd"),
            array("/qwe/qwe", "asd/asd", "/qwe/qwe/asd/asd"),
            array("/", ".", "/."),
            array("/", "..", "/.."),
            array("/", "/", "/"),
            array("/a/b/c/", "d", "/a/b/c/d"),
        );
    }

    /**
     * @dataProvider expendP
     */
    public function testExpand($base, $path, $expect)
    {
        $p = new Path($path, $base, "/");
        $actual = $p->expand();
        $this->assertEquals($expect, $actual);
    }

    public function normalizeP()
    {
        return array(
            array("/", ".", "/"),
            array("/", "..", "/"),
            array("/b", "../../a", "/a"),
            array("/", "asd", "/asd"),
            array("/a/b/c/d/", "../../../e/f/g/", "/a/e/f/g"),
        );
    }

    /**
     * @dataProvider normalizeP
     */
    public function testNormalize($base, $path, $expect)
    {
        $p = new Path($path, $base, "/");
        $actual = $p->normalize();
        $this->assertEquals($expect, $actual);
    }

    public function withinP()
    {
        return array(
            array("/a", "/a/b/c", true),
            array("/b", "/a/b/c", false),
            array("/b", "/a/b", false),
            array("/a/b/..", "/a/c", true),
            array("/a/b", "c/d/../../e/f", true),
            array("/a/b", "c/d/../../../e/f", false),
        );
    }

    /**
     * @dataProvider withinP
     */
    public function testWithin($base, $path, $expect)
    {
        $p = new Path($path, $base, "/");
        $actual = $p->within();
        $this->assertEquals($expect, $actual);
    }

    public function relativeP()
    {
        return array(
            array("/a", "/a/b/c", "b/c"),
            array("/a/b", "/a/c", "../c"),
            array("/a", "/d/b/c", "../d/b/c"),
            array("/a", ".", "."),
            array("/", ".", "."),
            array("/", "..", "."),
            array("/", "/", "."),
            array("/a/b", "..", ".."),
            array("/a", "..", ".."),
        );
    }

    /**
     * @dataProvider relativeP
     */
    public function testRelative($base, $path, $expect)
    {
        $p = new Path($path, $base, "/");
        $actual = $p->relative();
        $this->assertEquals($expect, $actual);
    }

    public function absP()
    {
        return array(
            array("/", true),
            array("//", true),
            array("/..", true),
            array("..", false),
            array(".", false),
            array("a", false),
            array("a/../../../../../../../../../../.././../../../../", false),
            array("a/../../../../../../../../../../.././../../../..//////", false),
        );
    }

    /**
     * @dataProvider absP
     */
    public function testAbsolute($path, $expect)
    {
        $p = new Path($path, "/", "/");
        $actual = $p->isAbsolute();
        $this->assertEquals($expect, $actual);
    }
}
