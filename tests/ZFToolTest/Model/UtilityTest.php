<?php
namespace ZFToolTest\Model;

use ZFTool\Model\Utility;

class UtilityTest extends \PHPUnit_Framework_TestCase
{

    protected $tmp;

    public function setUp()
    {
        $this->tmp = sys_get_temp_dir() . '/testZFTool';

        mkdir ($this->tmp);
        file_put_contents($this->tmp . '/foo', 'bar');
        chmod ($this->tmp . '/foo', 0755);
        mkdir ($this->tmp . '/foo-dir');
        file_put_contents($this->tmp . '/foo-dir/foo2', 'bar2');
    }

    public function tearDown()
    {
        @unlink($this->tmp . '/foo-dir/foo2');
        @rmdir($this->tmp . '/foo-dir');
        @unlink($this->tmp . '/foo');
        @rmdir($this->tmp);
    }

    public function testCopyFiles()
    {
        $tmpDir2 = $this->tmp . '2';

        $result = Utility::copyFiles( $this->tmp, $tmpDir2);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($tmpDir2 . '/foo'));
        $this->assertEquals('bar', file_get_contents($tmpDir2 . '/foo'));
        $this->assertEquals(fileperms($this->tmp . '/foo'), fileperms($tmpDir2 . '/foo'));
        $this->assertTrue(file_exists($tmpDir2 . '/foo-dir'));
        $this->assertTrue(file_exists($tmpDir2 . '/foo-dir/foo2'));
        $this->assertEquals(fileperms($this->tmp . '/foo-dir/foo2'), fileperms($tmpDir2 . '/foo-dir/foo2'));
        $this->assertEquals('bar2', file_get_contents($tmpDir2 . '/foo-dir/foo2'));

        unlink($tmpDir2 . '/foo-dir/foo2');
        rmdir($tmpDir2 . '/foo-dir');
        unlink($tmpDir2 . '/foo');
        rmdir($tmpDir2);
    }

    public function testDeleteFolder()
    {
        $result = Utility::deleteFolder($this->tmp);
        $this->assertTrue($result);
        $this->assertFalse(file_exists($this->tmp));
    }

}
