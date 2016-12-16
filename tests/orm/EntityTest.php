<?php
declare(strict_types = 1);

namespace mheinzerling\entity\orm;

use mheinzerling\commons\database\TestDatabaseConnection;
use mheinzerling\test2\User;
use mheinzerling\test2\UserRepository;

class EntityTest extends \PHPUnit_Framework_TestCase
{

    public function testFetchAllEmpty()
    {
        $conn = new TestDatabaseConnection();
        $repo = new UserRepository($conn);
        $repo->initialize();
        static::assertEquals([], $repo->fetchAll());
    }

    public function testPersistFetchAll()
    {
        $conn = new TestDatabaseConnection();
        $repo = new UserRepository($conn);
        $repo->initialize();
        $foo = new User();
        $foo->setNick("foo");
        $foo->setBirthday(new \DateTime());
        $bar = new User();
        $bar->setNick("bar");
        $repo->persist($foo);
        $repo->persist($bar); //TODO

        static::assertEquals([$foo, $bar], $repo->fetchAll());
    }

    public function testFetchUserById()
    {
        $conn = new TestDatabaseConnection();
        $repo = new UserRepository($conn);
        $repo->initialize();
        $foo = new User();
        $foo->setNick("foo");
        $foo->setBirthday(new \DateTime());
        $repo->persist($foo);

        $bar = new User();
        $bar->setNick("bar");
        $repo->persist($bar);

        static::assertEquals($bar, $repo->fetchById($bar->getId()));
    }
}