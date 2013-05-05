<?php

namespace mheinzerling\entity;


use mheinzerling\commons\JsonUtils;

class ClassGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testListOfFilesAndPaths()
    {

        $json = '{"gensrc": "gensrc","src": "src","entities": {
         "Abc": {"namespace": "mheinzerling\\\\foo\\\\bar" },
         "mno": {  } ,
         "Xyz": { "namespace": "mheinzerling\\\\xyz" }}}';


        $gen = new ClassGenerator(JsonUtils::parseToArray($json));

        $files = array_keys($gen->generateFiles());
        $expected = array(
            "src/mheinzerling/foo/bar/Abc.php",
            "src/mheinzerling/foo/bar/AbcRepository.php",
            "gensrc/mheinzerling/foo/bar/AbcMetaData.php",
            "gensrc/mheinzerling/foo/bar/BaseAbc.php",
            "src/Mno.php",
            "src/MnoRepository.php",
            "gensrc/MnoMetaData.php",
            "gensrc/BaseMno.php",
            "src/mheinzerling/xyz/Xyz.php",
            "src/mheinzerling/xyz/XyzRepository.php",
            "gensrc/mheinzerling/xyz/XyzMetaData.php",
            "gensrc/mheinzerling/xyz/BaseXyz.php");

        $this->assertEquals($expected, $files);


    }
}
