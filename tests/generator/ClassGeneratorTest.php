<?php
declare(strict_types = 1);

namespace mheinzerling\entity\generator;


use mheinzerling\entity\config\Config;

class ClassGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testListOfFilesAndPaths()
    {
        $json = '{"gensrc": "gensrc","src": "src","model":"\\\\mheinzerling\\\\TestModel","entities": {
         "Abc": {"namespace": "\\\\mheinzerling\\\\foo\\\\bar", "xyz": { "type": "Xyz" } },
         "mno": {"namespace": "\\\\" } ,
         "Xyz": { "namespace": "\\\\mheinzerling\\\\xyz","id": {"type":"int", "auto": true,"primary": true} }},
         "enums":{ "Foo": {"namespace": "\\\\mheinzerling\\\\foo", "values":{"a":"b"} }}}';

        $config = Config::loadJson($json);


        $files = array_keys($config->generateFiles());
        $expected = [
            "src/mheinzerling/foo/bar/Abc.php",
            "src/mheinzerling/foo/bar/AbcRepository.php",
            "gensrc/mheinzerling/foo/bar/BaseAbcRepository.php",
            "gensrc/mheinzerling/foo/bar/BaseAbc.php",
            "src/Mno.php",
            "src/MnoRepository.php",
            "gensrc/BaseMnoRepository.php",
            "gensrc/BaseMno.php",
            "src/mheinzerling/xyz/Xyz.php",
            "src/mheinzerling/xyz/XyzRepository.php",
            "gensrc/mheinzerling/xyz/BaseXyzRepository.php",
            "gensrc/mheinzerling/xyz/BaseXyz.php",
            "src/mheinzerling/foo/Foo.php",
            "gensrc/mheinzerling/TestModel.php"];

        static::assertEquals($expected, $files);
    }
}

