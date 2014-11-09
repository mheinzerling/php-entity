<?php

namespace mheinzerling\entity;


use mheinzerling\commons\JsonUtils;

class ClassGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testListOfFilesAndPaths()
    {
        $json = '{"gensrc": "gensrc","src": "src","initializer":"mheinzerling","entities": {
         "Abc": {"namespace": "mheinzerling\\\\foo\\\\bar", "xyz": { "type": "Xyz" } },
         "mno": {  } ,
         "Xyz": { "namespace": "mheinzerling\\\\xyz","id": {"type":"Integer", "auto": true,"primary": true} }},
         "enums":{ "Foo": {"namespace": "mheinzerling\\\\foo", "values":{"a":"b"} }}}';

        $gen = new ClassGenerator(JsonUtils::parseToArray($json));

        $files = array_keys($gen->generateFiles());
        $expected = array(
            "src/mheinzerling/xyz/Xyz.php",
            "src/mheinzerling/xyz/XyzRepository.php",
            "gensrc/mheinzerling/xyz/BaseXyzRepository.php",
            "gensrc/mheinzerling/xyz/BaseXyz.php",
            "src/mheinzerling/foo/bar/Abc.php",
            "src/mheinzerling/foo/bar/AbcRepository.php",
            "gensrc/mheinzerling/foo/bar/BaseAbcRepository.php",
            "gensrc/mheinzerling/foo/bar/BaseAbc.php",
            "src/Mno.php",
            "src/MnoRepository.php",
            "gensrc/BaseMnoRepository.php",
            "gensrc/BaseMno.php",
            "src/mheinzerling/foo/Foo.php",
            "gensrc/mheinzerling/SchemaInitializer.php");

        $this->assertEquals($expected, $files);
    }

    public function testNamespaceAndForeignKeys()
    {
        $root = realpath(__DIR__ . "/../../..");
        $gen = ClassGenerator::loadFromFile($root . "/resources/tests/entities.json");
        $actual = $gen->getEntitiesRelations();
        $expected = array(
            'User' => array('namespace' => 'mheinzerling\test2', 'pk' => array('id'), 'fks' => array()),
            'Credential' => array('namespace' => 'mheinzerling\test', 'pk' => array("provider", "uid"),
                'fks' => array('user' => array('table' => 'user', 'fields' => array('id'), 'update' => 'CASCADE', 'delete' => 'RESTRICT')))

        );
        $this->assertEquals($expected, $actual);

    }
}
