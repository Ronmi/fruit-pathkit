<?php

namespace FruitTest\PathKit;

use Fruit\PathKit\Glob;

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
}
