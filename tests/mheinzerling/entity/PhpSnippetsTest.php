<?php
namespace mheinzerling\entity;


use mheinzerling\commons\JsonUtils;

class PhpSnippetsTest extends \PHPUnit_Framework_TestCase
{

    public function testRepository()
    {
        $actual = PhpSnippets::repository("Foo", 'foo\foo');
        $expected = '<?php
namespace foo\foo;

use mheinzerling\entity\EntityRepository;

class FooRepository extends EntityRepository
{
}';
        $this->assertEquals($expected, $actual);
    }

    public function testRepositoryWithoutNamespace()
    {
        $actual = PhpSnippets::repository("Foo", "");
        $expected = '<?php
use mheinzerling\entity\EntityRepository;

class FooRepository extends EntityRepository
{
}';
        $this->assertEquals($expected, $actual);
    }

    public function testEntity()
    {
        $actual = PhpSnippets::entity("Foo", 'foo\foo');
        $expected = '<?php
namespace foo\foo;

class Foo extends BaseFoo
{
}';
        $this->assertEquals($expected, $actual);
    }

    public function testMetaData()
    {
        $json = JsonUtils::parseToArray(file_get_contents(__DIR__ . "/../../../resources/tests/entities.json"));


        $json['entities']['Credential']['user']['type'] = '\mheinzerling\test2\User';
        $actual = PhpSnippets::metadata('Credential', $json['entities']['Credential']);
        $expected = "<?php
return array(
    'name' => 'Credential',
    'repoClass' => 'mheinzerling\\test\\CredentialRepository',
    'baseClass' => 'mheinzerling\\test\\BaseCredential',
    'entityClass' => 'mheinzerling\\test\\Credential',
    'namespace' => 'mheinzerling\\test',
    'table' => 'credential',
    'fields' => array(
        'provider' => array('type' => 'String', 'length' => 255, 'primary' => true),
        'uid' => array('type' => 'String', 'length' => 255, 'primary' => true),
        'user' => array('type' => '\\mheinzerling\\test2\\User', 'optional' => true)),
    'pk' => array('provider', 'uid'),
    'autoincrement' => null
);";
        $this->assertEquals($expected, $actual);

        $actual = PhpSnippets::metadata('User', $json['entities']['User']);
        $expected = "<?php
return array(
    'name' => 'User',
    'repoClass' => 'mheinzerling\\test2\\UserRepository',
    'baseClass' => 'mheinzerling\\test2\\BaseUser',
    'entityClass' => 'mheinzerling\\test2\\User',
    'namespace' => 'mheinzerling\\test2',
    'table' => 'user',
    'fields' => array(
        'id' => array('type' => 'Integer', 'auto' => true, 'primary' => true),
        'nick' => array('type' => 'String', 'length' => 100),
        'birthday' => array('type' => '\DateTime', 'optional' => true)),
    'pk' => array('id'),
    'autoincrement' => 'id'
);";
        $this->assertEquals($expected, $actual);

    }

    public function testBase()
    {
        $json = JsonUtils::parseToArray(file_get_contents(__DIR__ . "/../../../resources/tests/entities.json"));


        $json['entities']['Credential']['user']['type'] = '\mheinzerling\test2\User';
        $foreignKeys = array('\mheinzerling\test2\User' => 'id');
        $actual = PhpSnippets::base('Credential', $json['entities']['Credential'], $foreignKeys);
        $expected = '<?php
namespace mheinzerling\test;

use mheinzerling\entity\Entity;
use mheinzerling\entity\EntityProxy;

abstract class BaseCredential extends Entity
{
    /**
     * @var String
     */
    protected $provider;

    /**
     * @var String
     */
    protected $uid;

    /**
     * @var \mheinzerling\test2\User
     */
    protected $user;

    public function __construct()
    {
        if (!$this->user instanceof \mheinzerling\test2\User && $this->user != null) {
            $this->user = new EntityProxy(\'\mheinzerling\test2\UserRepository\', array(\'id\' => $this->user));
        }
    }

    /**
     * @param String $provider
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return String
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param String $uid
     */
    public function setUid($uid)
    {
        $this->uid = $uid;
    }

    /**
     * @return String
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @param \mheinzerling\test2\User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return \mheinzerling\test2\User
     */
    public function getUser()
    {
        return $this->user;
    }

}';
        $this->assertEquals($expected, $actual);

        $actual = PhpSnippets::base('User', $json['entities']['User'], array());
        $expected = '<?php
namespace mheinzerling\test2;

use mheinzerling\entity\Entity;

abstract class BaseUser extends Entity
{
    /**
     * @var Integer
     */
    protected $id;

    /**
     * @var String
     */
    protected $nick;

    /**
     * @var \DateTime
     */
    protected $birthday;

    public function __construct()
    {
        if (!$this->birthday instanceof \DateTime && $this->birthday != null) {
            $this->birthday = new \DateTime($this->birthday);
        }
    }

    /**
     * @param Integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return Integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param String $nick
     */
    public function setNick($nick)
    {
        $this->nick = $nick;
    }

    /**
     * @return String
     */
    public function getNick()
    {
        return $this->nick;
    }

    /**
     * @param \DateTime $birthday
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;
    }

    /**
     * @return \DateTime
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

}';
        $this->assertEquals($expected, $actual);

    }
}
