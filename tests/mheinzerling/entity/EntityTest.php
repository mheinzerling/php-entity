<?php
namespace mheinzerling\entity;

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
        $this->assertEquals(array(), $repo->fetchAll());
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

        $this->assertEquals(array($foo, $bar), $repo->fetchAll());
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

        $this->assertEquals($bar, $repo->fetchById($bar->getId()));
    }
}