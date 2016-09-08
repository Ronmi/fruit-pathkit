<?php

namespace FruitTest\PathKit;

use Fruit\PathKit\Glob;
use Fruit\PathKit\Path;

class GlobTest extends \PHPUnit_Framework_TestCase
{
    public function regP()
    {
        return json_decode(file_get_contents(__DIR__ . '/assets/glob.regex.json'));
    }

    /**
     * @dataProvider regP
     */
    public function testRegexp($pattern, $path, $expect, $msg)
    {
        $g = new Glob($pattern, "/");
        $regexp = $g->regex();

        if ($expect) {
            $this->assertRegExp($regexp, $path, sprintf(
                "%s: pattern, path: [%s], [%s] expected to match",
                $msg, $pattern, $path
            ));
        } else {
            $this->assertNotRegExp($regexp, $path, sprintf(
                "%s: pattern, path: [%s], [%s] expected to not match",
                $msg, $pattern, $path
            ));
        }
    }

    public function testStaticIterator()
    {
        $g = new Glob("test/assets/glob.regex.json", "/");
        $cnt = 0;
        $expect = (new Path("test/assets/glob.regex.json"))->normalize();
        foreach ($g->iterate() as $v) {
            $this->assertEquals($expect, $v);
            $cnt++;
        }
        $this->assertEquals(1, $cnt);
    }

    public function dynP()
    {
        return array(
            array('test/*/glob.regex.json', 1),
            array('*/assets/glob.regex.json', 1),
            array('test/assets/glob.regex.*', 1),
            array('test/a*/glob.regex.*', 1),
            array('t*/a*/glob.regex.*', 1),
        );
    }

    /**
     * @dataProvider dynP
     */
    public function testDynamicIterator($pattern, $expect)
    {
        $g = new Glob($pattern, "/");
        $regex = $g->regex();
        $cnt = 0;
        foreach ($g->iterate() as $v) {
            $this->assertRegExp($regex, $v);
            $cnt++;
        }
        $this->assertEquals($expect, $cnt);
    }

    public function dynFaultP()
    {
        return array(
            array('src/assets/*'),
            array('test/assets/dyn_faulty/*.dot'),
        );
    }

    /**
     * @dataProvider dynFaultP
     */
    public function testDynamicIteratorError($pattern)
    {
        $g = new Glob($pattern, "/");
        $regex = $g->regex();
        $cnt = 0;
        foreach ($g->iterate() as $v) {
            $this->assertTrue(false, $v . ' should not be here');
            $cnt++;
        }
        $this->assertEquals(0, $cnt);
    }
}
