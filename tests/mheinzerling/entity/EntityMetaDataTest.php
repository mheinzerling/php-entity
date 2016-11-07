<?php
namespace mheinzerling\entity;

use mheinzerling\commons\database\PersistenceProvider;
use mheinzerling\commons\database\TestDatabaseConnection;
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
        $expected = [
            'name' => 'User',
            'repoClass' => 'mheinzerling\test2\UserRepository',
            'baseClass' => 'mheinzerling\test2\BaseUser',
            'entityClass' => 'mheinzerling\test2\User',
            'namespace' => 'mheinzerling\test2',
            'table' => 'user',
            'fields' => [
                'id' => ['type' => 'Integer', 'auto' => 1, 'primary' => 1],
                'nick' => ['type' => 'String', 'length' => 100],
                'birthday' => ['type' => '\DateTime', 'optional' => 1],
                'active' => ['type' => 'Boolean', 'default' => 0],
                'gender' => ['type' => '\mheinzerling\test2\Gender', 'optional' => 1, 'values' => ['m' => 'male', 'f' => 'female']]],
            'pk' => ['id'],
            'fks' => [],
            'unique' => ['nick' => ['nick']],
            'autoincrement' => 'id'
        ];
        static::assertEquals($expected, (array)$actual);
    }

    public function testMetaDataPk()
    {
        $repo = new CredentialRepository(null);
        $actual = new EntityMetaData($repo);
        $expected = [
            'name' => 'Credential',
            'repoClass' => 'mheinzerling\test\CredentialRepository',
            'baseClass' => 'mheinzerling\test\BaseCredential',
            'entityClass' => 'mheinzerling\test\Credential',
            'namespace' => 'mheinzerling\test',
            'table' => 'credential',
            'fields' => [
                'provider' => ['type' => 'String', 'length' => 255, 'primary' => 1],
                'uid' => ['type' => 'String', 'length' => 255, 'primary' => 1],
                'user' => ['type' => '\mheinzerling\test2\User', 'optional' => 1]],
            'pk' => ['provider', 'uid'],
            'fks' => ['user' => ['table' => 'user', 'fields' => ['id'], 'update' => 'CASCADE', 'delete' => 'RESTRICT']],
            'unique' => [],
            'autoincrement' => null
        ];
        static::assertEquals($expected, (array)$actual);
    }

    public function testSchema()
    {
        $meta = new EntityMetaData(new UserRepository(null));
        $expected = "CREATE TABLE `user` (`id` INT NOT NULL AUTO_INCREMENT, `nick` VARCHAR(100) NOT NULL, `birthday` DATETIME NULL, `active` INT(1) NOT NULL DEFAULT '0', `gender` ENUM('m', 'f') NULL, " .
            "PRIMARY KEY (`id`), " .
            "UNIQUE KEY `idx_user_nick` (`nick`));";
        $actual = $meta->buildSchema();
        static::assertEquals($expected, $actual);
    }

    public function testSchemaPk()
    {
        $meta = new EntityMetaData(new CredentialRepository(null));
        $expected = "CREATE TABLE `credential` (`provider` VARCHAR(255) NOT NULL, `uid` VARCHAR(255) NOT NULL, `user` INT NULL, " .
            "PRIMARY KEY (`provider`,`uid`), " .
            "FOREIGN KEY `fk_credential_user_id`(`user`) REFERENCES user(`id`) ON UPDATE CASCADE ON DELETE RESTRICT" .
            ");";
        $actual = $meta->buildSchema();
        static::assertEquals($expected, $actual);
    }
}
