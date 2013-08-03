<?php
namespace mheinzerling\entity;

use mheinzerling\commons\database\TestDatabaseConnection;
use mheinzerling\entity\EntityMetaData;
use mheinzerling\test\CredentialRepository;
use mheinzerling\test2\UserRepository;

class EntityMetaDataTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        PersistenceProvider::setConnection(new TestDatabaseConnection());
    }

    public function testMetaDataRevision()
    {
        $repo = new UserRepository(null);
        $actual = new EntityMetaData($repo);
        $expected = array(
            'name' => 'User',
            'repoClass' => 'mheinzerling\test2\UserRepository',
            'baseClass' => 'mheinzerling\test2\BaseUser',
            'entityClass' => 'mheinzerling\test2\User',
            'namespace' => 'mheinzerling\test2',
            'table' => 'user',
            'fields' => array(
                'id' => array('type' => 'Integer', 'auto' => 1, 'primary' => 1),
                'nick' => array('type' => 'String', 'length' => 100),
                'birthday' => array('type' => '\DateTime', 'optional' => 1),
                'active' => array('type' => 'Boolean', 'default' => 0)),
            'unique' => array('nick' => array('nick')),
            'pk' => array('id'),
            'autoincrement' => 'id'
        );
        $this->assertEquals($expected, (array)$actual);
    }

    public function testMetaDataPk()
    {
        $repo = new CredentialRepository(null);
        $actual = new EntityMetaData($repo);
        $expected = array(
            'name' => 'Credential',
            'repoClass' => 'mheinzerling\test\CredentialRepository',
            'baseClass' => 'mheinzerling\test\BaseCredential',
            'entityClass' => 'mheinzerling\test\Credential',
            'namespace' => 'mheinzerling\test',
            'table' => 'credential',
            'fields' => array(
                'provider' => array('type' => 'String', 'length' => 255, 'primary' => 1),
                'uid' => array('type' => 'String', 'length' => 255, 'primary' => 1),
                'user' => array('type' => '\mheinzerling\test2\User', 'optional' => 1)),
            'pk' => array('provider', 'uid'),
            'unique' => array(),
            'autoincrement' => null
        );
        $this->assertEquals($expected, (array)$actual);
    }

    public function testSchema()
    {
        $meta = new EntityMetaData(new UserRepository(null));
        $expected = "CREATE TABLE `user` (`id` INT NOT NULL AUTO_INCREMENT,`nick` VARCHAR(100) NOT NULL,`birthday` DATETIME NULL,`active` INT(1) NOT NULL DEFAULT '0',PRIMARY KEY (`id`),UNIQUE KEY `nick` (`nick`));";
        $actual = $meta->buildSchema();
        $this->assertEquals($expected, $actual);
    }

    public function testSchemaPk()
    {
        $meta = new EntityMetaData(new CredentialRepository(null));
        $expected = "CREATE TABLE `credential` (`provider` VARCHAR(255) NOT NULL,`uid` VARCHAR(255) NOT NULL,`user` INT(11) NULL,PRIMARY KEY (`provider`,`uid`));";
        $actual = $meta->buildSchema();
        $this->assertEquals($expected, $actual);
    }
}
