<?php
namespace mheinzerling\entity;

use mheinzerling\commons\database\LoggingPDO;
use mheinzerling\commons\database\PersistenceProvider;
use mheinzerling\commons\database\TestDatabaseConnection;
use mheinzerling\test\Credential;
use mheinzerling\test\CredentialRepository;
use mheinzerling\test2\Gender;
use mheinzerling\test2\User;
use mheinzerling\test2\UserRepository;

class CredentialsTest extends \PHPUnit_Framework_TestCase
{
    public function testPersistFetchEntityRef()
    {
        PersistenceProvider::setConnection(new TestDatabaseConnection(false));
        $users = new UserRepository();
        $users->initialize();
        $credentials = new CredentialRepository();
        $credentials->initialize();

        LoggingPDO::clearLog();
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
        static::assertEquals(3, LoggingPDO::numberOfQueries(), LoggingPDO::getLog());
        $dbUser = $dbCred->getUser(); //only proxy
        static::assertEquals(3, LoggingPDO::numberOfQueries(), LoggingPDO::getLog());
        $dbId = $dbUser->getId(); // only pk of proxy
        static::assertEquals(1, $dbId);
        static::assertEquals(3, LoggingPDO::numberOfQueries(), LoggingPDO::getLog());
        static::assertEquals("mnhg", $dbUser->getNick());
        static::assertEquals(Gender::MALE(), $dbUser->getGender());
        static::assertEquals(4, LoggingPDO::numberOfQueries(), LoggingPDO::getLog());
        $users->persist($dbUser);
    }
}
