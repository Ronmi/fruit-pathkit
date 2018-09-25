<?php

namespace FruitTest\PathKit;

use Fruit\PathKit\Path;

class PathUnixTest extends \PHPUnit\Framework\TestCase
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

            // test against asterisk
            array("/qwe", "*", "/qwe/*"),
            array("/qwe/qwe", "*/*", "/qwe/qwe/*/*"),
            array("/a/b/c/", "../*", "/a/b/c/../*"),
            array("/", "*", "/*"),
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
            array("/a", "b", "/a/b"),
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

            // test against asterisk
            array("/a", "/a/b/*", true),
            array("/b", "/a/b/*", false),
            array("/b", "/a/*", false),
            array("/a/b/..", "/a/*", true),
            array("/a/b", "c/d/../../*", true),
            array("/a/b", "c/d/../../../*", false),
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
            array("/a", "/a/b/c", '', "b/c"),
            array("/a/b", "/a/c", '', "../c"),
            array("/a", "/d/b/c", '', "../d/b/c"),
            array("/a", ".", '', "."),
            array("/", ".", '', "."),
            array("/", "..", '', "."),
            array("/", "/", '', "."),
            array("/a/b", "..", '', ".."),
            array("/a", "..", '', ".."),
            array("/", "a", '', "a"),
            array("/work/temp", "../my.file", "/another/work", "../../work/my.file"),

            // test against asterisk
            array("/a", "/a/*/c", '', "*/c"),
            array("/a/b", "/a/*", '', "../*"),
            array("/a", "/d/*/c", '', "../d/*/c"),
            array("/a", "*", '', "*"),
            array("/", "*", '', "*"),
            array("/", "../*", '', "*"),
            array("/work/temp", "../*", "/another/work", "../../work/*"),
        );
    }

    /**
     * @dataProvider relativeP
     */
    public function testRelative($base, $path, $rel, $expect)
    {
        $p = new Path($path, $base, "/");
        $actual = $p->relative($rel);
        $this->assertEquals($expect, $actual);
    }

    public function absP()
    {
        return array(
            array("/", true),
            array("//", true),
            array("/..", true),
            array("..", false),
            array("../a", false),
            array(".", false),
            array("./a", false),
            array("a", false),
            array("a/../../../../../../../../../../.././../../../../", false),
            array("a/../../../../../../../../../../.././../../../..//////", false),

            // test against asterisk
            array("/*", true),
            array("/../*", true),
            array("../*", false),
            array("./*", false),
            array("*", false),
            array("*/../../../../../../../../../../.././../../../../", false),
            array("*/../../../../../*/../../../../.././../../../../", false),
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

    public function testJoin()
    {
        $p = new Path('a', '/', '/');
        $p->join('b', '/c', 'd/', '/e/', 'f');
        $expect = 'a/b/c/d/e/f';
        $actual = $p->path;
        $this->assertEquals($expect, $actual);
    }
}
