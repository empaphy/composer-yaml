<?php

/**
 * @copyright 2022 Alwin Garside
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace Empaphy\Composer\Test\Yaml;

use Empaphy\Composer\Yaml\YamlFile;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Alwin Garside <alwin@garsi.de>
 */
class YamlFileTest extends TestCase
{
    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    private $vfsRoot;

    /**
     * Sets up the fixture.
     *
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp()
    {
        $this->vfsRoot = vfsStream::setup();
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    public function testImportJsonFile()
    {
        $jsonFile = $this->createMock('Composer\Json\JsonFile');
        $jsonFile->expects(self::once())
            ->method('read')
            ->willReturn(array('foo' => 'bar'));

        $yamlFile = new YamlFile("{$this->vfsRoot->url()}/test.yaml");
        $yamlFile->importJsonFile($jsonFile);
    }

    /**
     * @return void
     */
    public function testEncode()
    {
        $data = array('foo' => 'bar');

        $expected = Yaml::dump($data);
        $actual   = YamlFile::encode($data);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return void
     */
    public function testParseYaml()
    {
        $yaml = 'foo: "bar"';

        $this->assertNull(YamlFile::parseYaml(null));

        $expected = Yaml::parse($yaml, 0);
        $actual = YamlFile::parseYaml($yaml);

        $this->assertEquals($expected, $actual);
    }

//    /**
//     * @return void
//     * @throws \Exception
//     */
//    public function testWrite()
//    {
//        // Test case: non-existing file.
//        $yamlFile = new YamlFile("{$this->vfsRoot->url()}/test.yaml");
//
//        $yamlFile->write(array('foo' => 'bar'));
//
//        // @TODO test case with existing file.
//        // @TODO test case with $rms.
//    }
}
