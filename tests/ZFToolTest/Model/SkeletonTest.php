<?php
namespace ZFToolTest\Model;

use ZFTool\Model\Skeleton;
use Zend\Code\Generator\ValueGenerator;

class SkeletonTest extends \PHPUnit_Framework_TestCase
{

    public function testGetLastCommit()
    {
        $result = Skeleton::getLastCommit();
        if (false !== $result) {
            $this->assertTrue(is_array($result));
        } else {
            $this->markTestSkipped('The github API is not accessible.');
        }
    }

    public function testGetSkeletonApp()
    {
        $tmpFile = sys_get_temp_dir() . '/testZFTool.zip';
        $result  = Skeleton::getSkeletonApp($tmpFile);
        if ($result) {
            $this->assertTrue(file_exists($tmpFile));
            @unlink($tmpFile);
        } else {
            $this->markTestSkipped('The ZF2 Skeleton github repository is not accessible.');
        }
    }

    public function testGetLastZip()
    {
        $tmpFile = sys_get_temp_dir() . '/' . Skeleton::SKELETON_FILE . '_test.zip';
        Skeleton::getSkeletonApp($tmpFile);
        if (file_exists($tmpFile)) {
            $result = Skeleton::getLastZip(sys_get_temp_dir());
            $this->assertTrue(!empty($result));
            unlink($tmpFile);
        } else {
            $this->markTestSkipped('The ZF2 Skeleton github repository is not accessible.');
        }
    }

    public function testGetTmpFileName()
    {
        $commit = array('sha' => 'test');
        $path   = sys_get_temp_dir();
        $result = Skeleton::getTmpFileName($path, $commit);
        $this->assertEquals($result, $path . '/' . Skeleton::SKELETON_FILE . '_test.zip');
    }

    public function testGetTmpFileNameWrongCommit()
    {
        $commit = array('foo' => 'bar');
        $path   = sys_get_temp_dir();
        $result = Skeleton::getTmpFileName($path, $commit);
        $this->assertEquals('', $result);
    }

    public function testExportConfig()
    {
        $config = array(
            'foo' => array(
                'foo2' => 'bar2',
                'foo3' => 'bar3'
            ),
            'bar'
        );
        $export = Skeleton::exportConfig($config);
        $expected = <<<EOD
array(
    'foo' => array(
        'foo2' => 'bar2',
        'foo3' => 'bar3'
        ),
    'bar'
    )
EOD;
        $this->assertEquals($expected, $export);
    }
}
