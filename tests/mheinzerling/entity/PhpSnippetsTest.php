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

class FooRepository extends BaseFooRepository
{
}';
        $this->assertEqualsIgnoreLineEnding($expected, $actual);
    }

    public function testRepositoryWithoutNamespace()
    {
        $actual = PhpSnippets::repository("Foo", "");
        $expected = '<?php
class FooRepository extends BaseFooRepository
{
}';
        $this->assertEqualsIgnoreLineEnding($expected, $actual);
    }

    public function testEntity()
    {
        $actual = PhpSnippets::entity("Foo", 'foo\foo');
        $expected = '<?php
namespace foo\foo;

class Foo extends BaseFoo
{
}';
        $this->assertEqualsIgnoreLineEnding($expected, $actual);
    }

    public function testMetaData()
    {
        $json = JsonUtils::parseToArray(file_get_contents(__DIR__ . "/../../../resources/tests/entities.json"));


        $json['entities']['Credential']['user']['type'] = '\mheinzerling\test2\User';
        $actual = PhpSnippets::baserepository('Credential', $json['entities']['Credential'],
            ['user' => ['table' => 'user', 'fields' => ['id'], 'update' => 'CASCADE', 'delete' => 'RESTRICT']]);

        $expected = "<?php
namespace mheinzerling\\test;

use mheinzerling\\entity\\EntityRepository;

class BaseCredentialRepository extends EntityRepository
{
    public function getMeta()
    {
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
            'fks' => array('user' => array('table' => 'user', 'fields' => array('id'), 'update' => 'CASCADE', 'delete' => 'RESTRICT')),
            'unique' => array(),
            'autoincrement' => null
        );
    }

    /**
     * @return Credential
     */
    public function fetchByProviderAndUid(\$provider, \$uid)
    {
        return \$this->fetchUnique(\"WHERE `provider`=:provider AND `uid`=:uid\", array('provider'=>\$provider, 'uid'=>\$uid));
    }
}";
        $this->assertEqualsIgnoreLineEnding($expected, $actual);

        $json['entities']['User']['gender']['values'] = ['m' => 'male', 'f' => 'female'];
        $actual = PhpSnippets::baserepository('User', $json['entities']['User'], []);
        $expected = "<?php
namespace mheinzerling\\test2;

use mheinzerling\\entity\\EntityRepository;

class BaseUserRepository extends EntityRepository
{
    public function getMeta()
    {
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
                'birthday' => array('type' => '\DateTime', 'optional' => true),
                'active' => array('type' => 'Boolean', 'default' => 0),
                'gender' => array('type' => 'Gender', 'optional' => true, 'values' => array('m' => 'male', 'f' => 'female'))),
            'pk' => array('id'),
            'fks' => array(),
            'unique' => array('nick'=>array('nick')),
            'autoincrement' => 'id'
        );
    }

    /**
     * @return User
     */
    public function fetchById(\$id)
    {
        return \$this->fetchUnique(\"WHERE `id`=:id\", array('id'=>\$id));
    }
}";
        $this->assertEqualsIgnoreLineEnding($expected, $actual);

    }

    public function testBase()
    {
        $json = JsonUtils::parseToArray(file_get_contents(__DIR__ . "/../../../resources/tests/entities.json"));


        $json['entities']['Credential']['user']['type'] = '\mheinzerling\test2\User';
        $foreignKeys = ['User' => ['pk' => ['id']]];
        $actual = PhpSnippets::base('Credential', $json['entities']['Credential'], $foreignKeys, []);
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
        $this->assertEqualsIgnoreLineEnding($expected, $actual);
        $json['entities']['User']['gender']['type'] = "\\mheinzerling\\test2\\Gender";
        $actual = PhpSnippets::base('User', $json['entities']['User'], [], ["Gender" => ['namespace' => 'mheinzerling\\test2']]);
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

    /**
     * @var Boolean
     */
    protected $active;

    /**
     * @var \mheinzerling\test2\Gender
     */
    protected $gender;

    public function __construct()
    {
        if (!$this->birthday instanceof \DateTime && $this->birthday != null) {
            $this->birthday = new \DateTime($this->birthday);
        }
        if (!$this->gender instanceof \mheinzerling\test2\Gender && $this->gender != null) {
            $this->gender = \mheinzerling\test2\Gender::memberByValue(strtoupper($this->gender));
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

    /**
     * @param Boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @return Boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param \mheinzerling\test2\Gender $gender
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
    }

    /**
     * @return \mheinzerling\test2\Gender
     */
    public function getGender()
    {
        return $this->gender;
    }

}';
        $this->assertEqualsIgnoreLineEnding($expected, $actual);

    }

    public function testInitializer()
    {
        $actual = PhpSnippets::initializer("mheinzerling", ['Credential' => ['namespace' => 'mheinzerling\test\\'],
            'User' => ['namespace' => 'mheinzerling\test2\\']]);
        $expected = "<?php
namespace mheinzerling;

class SchemaInitializer
{
    public function initialize()
    {
        \$repo = new \\mheinzerling\\test\\CredentialRepository();
        \$repo->initialize();
        \$repo = new \\mheinzerling\\test2\\UserRepository();
        \$repo->initialize();
    }
}";
        $this->assertEqualsIgnoreLineEnding($expected, $actual);
    }

    public function testEnum()
    {
        $actual = PhpSnippets::enum("mheinzerling", "Gender", ["m" => "male", "f" => "female"]);
        $expected = "<?php
namespace mheinzerling;

use Eloquent\\Enumeration\\AbstractEnumeration;

/**
 * @method static Gender MALE()
 * @method static Gender FEMALE()
 */
final class Gender extends AbstractEnumeration
{
    const MALE = 'M';
    const FEMALE = 'F';
}
";
        $this->assertEqualsIgnoreLineEnding($expected, $actual);
    }

    public function assertEqualsIgnoreLineEnding(string $expected, string $actual)
    {
        static::assertEquals(str_replace("\r", '', $expected), str_replace("\r", '', $actual));
    }
}
