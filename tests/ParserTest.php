<?php
use Parser\Parser;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase {


    private $root;

    protected function setUp() :void
    {
        $this->root = vfsStream::setup('exampleDir');
    }

    public function testInitializeFromValidXml()
    {
        $env = dirname(__FILE__) . '/..';
        $parser = new Parser($env);



    }

    public function testFileIsCreated() {
        $env = dirname(__FILE__) . '/..';
        $example = new Parser($env);
        $filename = 'hello.txt';
        $content = 'Hello world';
        $this->assertFalse($this->root->hasChild($filename));
        $example->saveAsJson(vfsStream::url($env .'exampleDir/' . $filename), $content);
        $this->assertTrue($this->root->hasChild($filename));
    }
}
