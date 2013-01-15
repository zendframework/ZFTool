<?php
namespace ZFTool\Model;

class Zf
{

    const LAST_VERSION    = 'http://framework.zend.com/api/zf-version?v=2';
    const GET_TAGS        = 'https://api.github.com/repos/zendframework/zf2/tags';
    const RELEASE_NAME    = 'release-';

    protected static $valueGenerator;

    protected static $tags = array();

    /**
     * Get the last zf2 version available
     *
     * @return string|boolean
     */
    public static function getLastVersion()
    {
        $content = @file_get_contents(self::LAST_VERSION);
        if (!empty($content)) {
            return $content;
        }
        return false;
    }

    /**
     * Check if a specified ZF version exists
     *
     * @param  string $version
     * @return boolean
     */
    public static function checkVersion($version)
    {
        $tags = self::getTags();
        return isset($tags[self::RELEASE_NAME . $version]);
    }

    /**
     * Get the tags of the ZF2 project
     *
     * @return array
     */
    public static function getTags()
    {
        if (empty(self::$tags)) {
            $tags = json_decode(@file_get_contents(self::GET_TAGS), true);
            foreach ($tags as $tag) {
                self::$tags[$tag['name']] = $tag['zipball_url'];
            }
        }
        return self::$tags;
    }

    /**
     * Download the ZF library in a zip file
     *
     * @param  string $version
     * @param  string $file
     * @return boolean
     */
    public static function downloadZip($file, $version)
    {
        $tags = self::getTags();
        if (!isset($tags[self::RELEASE_NAME . $version])) {
            return false;
        }
        $content = @file_get_contents($tags[self::RELEASE_NAME . $version]);
        if (empty($content)) {
            return false;
        }
       return (file_put_contents($file, $content) !== false);
    }

    /**
     * Get the temporary file name for the ZF2 library zip file
     *
     * @param  string $tmpDir
     * @param  string $version
     * @return string
     */
    public static function getTmpFileName($tmpDir, $version)
    {
        return "$tmpDir/ZF_$version.zip";
    }
}
