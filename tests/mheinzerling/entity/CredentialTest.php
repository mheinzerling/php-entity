<?php
namespace mheinzerling\entity;

use mheinzerling\commons\database\LoggingPDO;
use mheinzerling\commons\database\TestDatabaseConnection;
use mheinzerling\entity\PersistenceProvider;
use mheinzerling\test\Credential;
use mheinzerling\test\CredentialRepository;
use mheinzerling\test2\User;
use mheinzerling\test2\UserRepository;

class CredentialsTest extends \PHPUnit_Framework_TestCase
{
    public function testPersistFetchEntityRef()
    {
        PersistenceProvider::setConnection(new TestDatabaseConnection());
        $credentials = new CredentialRepository();
        $credentials->initialize();

        $users = new UserRepository();
        $users->initialize();
        LoggingPDO::clearLog();
        $user = new User();
        $user->setNick('mnhg');
        $this->assertEquals(null, $user->getId());
        $users->persist($user);
        $this->assertEquals(1, $user->getId());

        $credential = new Credential();
        $credential->setProvider('openid');
        $credential->setUid('http://www.myopenid.com/mnhg');
        $credential->setUser($user);

        $credentials->persist($credential);

        $dbCred = $credentials->fetchByProviderAndUid('openid', 'http://www.myopenid.com/mnhg');
        $this->assertEquals(3, LoggingPDO::numberOfQueries(), LoggingPDO::getLog());
        $dbUser = $dbCred->getUser(); //only proxy
        $this->assertEquals(3, LoggingPDO::numberOfQueries(), LoggingPDO::getLog());
        $dbId = $dbUser->getId(); // only pk of proxy
        $this->assertEquals(1, $dbId);
        $this->assertEquals(3, LoggingPDO::numberOfQueries(), LoggingPDO::getLog());
        $this->assertEquals("mnhg", $dbUser->getNick());
        $this->assertEquals(4, LoggingPDO::numberOfQueries(), LoggingPDO::getLog());
        $users->persist($dbUser);
    }
}
