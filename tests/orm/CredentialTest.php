<?php
declare(strict_types = 1);

namespace mheinzerling\entity;

use mheinzerling\commons\database\ConnectionProvider;
use mheinzerling\commons\database\TestDatabaseConnection;
use mheinzerling\test\Credential;
use mheinzerling\test\CredentialRepository;
use mheinzerling\test2\Gender;
use mheinzerling\test2\User;
use mheinzerling\test2\UserRepository;
use mheinzerling\TestModel;

class CredentialTest extends \PHPUnit_Framework_TestCase
{
    public function testPersistFetchEntityRef()
    {
        $pdo = new TestDatabaseConnection(true);
        ConnectionProvider::set($pdo);
        TestModel::getDatabase()->setName($pdo->getDatabaseName());
        TestModel::initialize($pdo, false);
        $users = new UserRepository();
        $credentials = new CredentialRepository();

        $pdo->clearLog();
        $user = new User();
        $user->setNick('mnhg');
        $user->setGender(Gender::MALE());
        static::assertEquals(null, $user->getId());
        $users->persist($user);
        static::assertEquals(1, $user->getId());

        $credential = new Credential();
        $credential->setProvider('openid');
        $credential->setUid('http://www.myopenid.com/mnhg');
        $credential->setUser($user);

        $credentials->persist($credential);

        $dbCred = $credentials->fetchByProviderAndUid('openid', 'http://www.myopenid.com/mnhg');
        static::assertEquals(3, $pdo->numberOfQueries(), $pdo->getLog());
        $dbUser = $dbCred->getUser(); //only proxy
        static::assertEquals(3, $pdo->numberOfQueries(), $pdo->getLog());
        $dbId = $dbUser->getId(); // only pk of proxy
        static::assertEquals(1, $dbId);
        static::assertEquals(3, $pdo->numberOfQueries(), $pdo->getLog());
        static::assertEquals("mnhg", $dbUser->getNick());
        static::assertEquals(Gender::MALE(), $dbUser->getGender());
        static::assertEquals(4, $pdo->numberOfQueries(), $pdo->getLog());
        $users->persist($dbUser);
    }
}
