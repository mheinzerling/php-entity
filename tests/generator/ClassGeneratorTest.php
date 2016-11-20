<?php

namespace mheinzerling\entity\generator;


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
        $expected = [
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
            "gensrc/mheinzerling/SchemaInitializer.php"];

        static::assertEquals($expected, $files);
    }

    public function testNamespaceAndForeignKeys()
    {
        $root = realpath(__DIR__ . "/../..");
        $gen = ClassGenerator::loadFromFile($root . "/resources/tests/entities.json");
        $actual = $gen->getEntitiesRelations();
        $expected = [
            'User' => ['namespace' => 'mheinzerling\test2', 'pk' => ['id'], 'fks' => []],
            'Credential' => ['namespace' => 'mheinzerling\test', 'pk' => ["provider", "uid"],
                'fks' => ['user' => ['table' => 'user', 'fields' => ['id'], 'update' => 'CASCADE', 'delete' => 'RESTRICT']]]

        ];
        static::assertEquals($expected, $actual);

    }
}
