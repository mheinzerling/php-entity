<?php
declare(strict_types = 1);

namespace mheinzerling\entity\generator;


use mheinzerling\entity\config\Config;
use mheinzerling\entity\config\Entity;
use mheinzerling\entity\config\Enum;

class PhpSnippetsTest extends \PHPUnit_Framework_TestCase
{

    public function testRepository()
    {

        $actual = (new Entity("Foo", AClass::of("\\foo\\Model"), ['namespace' => '\foo\foo']))->toRepositoryPHPFile();;
        $expected = '<?php
declare(strict_types = 1);

namespace foo\foo;

class FooRepository extends BaseFooRepository
{
}';
        $this->assertEqualsIgnoreLineEnding($expected, $actual);
    }

    public function testRepositoryWithoutNamespace()
    {
        $actual = (new Entity("Foo", AClass::of("\\Model"), ['namespace' => '\\']))->toRepositoryPHPFile();;
        $expected = '<?php
declare(strict_types = 1);

class FooRepository extends BaseFooRepository
{
}';
        $this->assertEqualsIgnoreLineEnding($expected, $actual);
    }

    public function testEntity()
    {
        $actual = (new Entity("Foo", AClass::of("\\foo\\Model"), ['namespace' => '\foo\foo']))->toEntityPHPFile();
        $expected = '<?php
declare(strict_types = 1);

namespace foo\foo;

class Foo extends BaseFoo
{
}';
        $this->assertEqualsIgnoreLineEnding($expected, $actual);
    }

    public function testMetaDataCredential()
    {
        $config = Config::loadFile(__DIR__ . "/../../resources/tests/entities.json");
        $actual = $config->getEntities()['Credential']->toRepositoryBasePHPFile();


        $expected = "<?php
declare(strict_types = 1);

namespace mheinzerling\\test;

use mheinzerling\\TestModel;
use mheinzerling\\entity\\generator\\ANamespace;
use mheinzerling\\entity\\orm\\EntityRepository;

abstract class BaseCredentialRepository extends EntityRepository
{
    public function __construct(\\PDO \$connection = null)
    {
        parent::__construct(
            \$connection,
            ANamespace::absolute('\\mheinzerling\\test'),
            'Credential',
            TestModel::getDatabase()
        );
    }

    public function fetchByProviderAndUid(string \$provider, string \$uid): ?Credential
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return \$this->fetchUnique(\"WHERE `provider`=:provider AND `uid`=:uid\", ['provider'=>\$provider, 'uid'=>\$uid]);
    }
}";
        $this->assertEqualsIgnoreLineEnding($expected, $actual);
    }

    public function testMetaDataUser()
    {

        $config = Config::loadFile(__DIR__ . "/../../resources/tests/entities.json");
        $actual = $config->getEntities()['User']->toRepositoryBasePHPFile();

        $expected = "<?php
declare(strict_types = 1);

namespace mheinzerling\\test2;

use mheinzerling\\TestModel;
use mheinzerling\\entity\\generator\\ANamespace;
use mheinzerling\\entity\\orm\\EntityRepository;

abstract class BaseUserRepository extends EntityRepository
{
    public function __construct(\\PDO \$connection = null)
    {
        parent::__construct(
            \$connection,
            ANamespace::absolute('\\mheinzerling\\test2'),
            'User',
            TestModel::getDatabase()
        );
    }

    public function fetchById(int \$id): ?User
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return \$this->fetchUnique(\"WHERE `id`=:id\", ['id'=>\$id]);
    }
}";
        $this->assertEqualsIgnoreLineEnding($expected, $actual);

    }

    public function testBaseCredential()
    {
        $config = Config::loadFile(__DIR__ . "/../../resources/tests/entities.json");
        $actual = $config->getEntities()['Credential']->toEntityBasePHPFile();
        $expected = '<?php
declare(strict_types = 1);

namespace mheinzerling\test;

use mheinzerling\entity\orm\Entity;
use mheinzerling\test2\User;

abstract class BaseCredential extends Entity
{
    /**
     * @var string
     */
    protected $provider;

    /**
     * @var string
     */
    protected $uid;

    /**
     * @var User|null
     */
    protected $user;

    public function __construct()
    {
        parent::__construct();
        if (!$this->user instanceof User && $this->user != null) {
            $pk = [\'id\' => intval($this->user)];
            $this->user = new User();
            $this->user->setPk($pk);
        }
    }

    public function getPk(): array
    {
        return [
            \'provider\' => $this->provider,
            \'uid\' => $this->uid
        ];
    }

    public function setPk(array $pk): void
    {
        $this->provider = $pk[\'provider\'];
        $this->uid = $pk[\'uid\'];
        $this->loaded = false;
    }

    public function setProvider(string $provider): void
    {
        $this->provider = $provider;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function setUid(string $uid): void
    {
        $this->uid = $uid;
    }

    public function getUid(): string
    {
        return $this->uid;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getUser(): ?User
    {
        $this->load();
        return $this->user;
    }
}';
        $this->assertEqualsIgnoreLineEnding($expected, $actual);
    }

    public function testBaseUser()
    {
        $config = Config::loadFile(__DIR__ . "/../../resources/tests/entities.json");
        $actual = $config->getEntities()['User']->toEntityBasePHPFile();
        $expected = '<?php
declare(strict_types = 1);

namespace mheinzerling\test2;

use mheinzerling\entity\orm\Entity;

abstract class BaseUser extends Entity
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $nick;

    /**
     * @var \DateTime|null
     */
    protected $birthday;

    /**
     * @var bool
     */
    protected $active = false;

    /**
     * @var Gender|null
     */
    protected $gender;

    public function __construct()
    {
        parent::__construct();
        $this->id = intval($this->id);
        if (!$this->birthday instanceof \DateTime && $this->birthday != null) {
            $this->birthday = new \DateTime($this->birthday);
        }
        $this->active = $this->active !== FALSE && $this->active !== \'0\';
        if (!$this->gender instanceof Gender && $this->gender != null) {
            $this->gender = Gender::memberByValue(strtoupper($this->gender));
        }
    }

    public function getPk(): array
    {
        return [
            \'id\' => $this->id
        ];
    }

    public function setPk(array $pk): void
    {
        $this->id = $pk[\'id\'];
        $this->loaded = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setNick(string $nick): void
    {
        $this->nick = $nick;
    }

    public function getNick(): string
    {
        $this->load();
        return $this->nick;
    }

    public function setBirthday(?\DateTime $birthday): void
    {
        $this->birthday = $birthday;
    }

    public function getBirthday(): ?\DateTime
    {
        $this->load();
        return $this->birthday;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function isActive(): bool
    {
        $this->load();
        return $this->active;
    }

    public function setGender(?Gender $gender): void
    {
        $this->gender = $gender;
    }

    public function getGender(): ?Gender
    {
        $this->load();
        return $this->gender;
    }
}';
        $this->assertEqualsIgnoreLineEnding($expected, $actual);

    }

    public function testModel()
    {
        $config = Config::loadFile(__DIR__ . "/../../resources/tests/entities.json");
        $actual = $config->toModelPHPFile();
        $expected = '<?php
declare(strict_types = 1);

namespace mheinzerling;

use mheinzerling\commons\database\structure\Database;
use mheinzerling\commons\database\structure\SqlSetting;
use mheinzerling\commons\database\structure\builder\DatabaseBuilder;
use mheinzerling\commons\database\structure\index\ReferenceOption;
use mheinzerling\commons\database\structure\type\Type;

class TestModel
{
    /**
     * @var Database
     */
    private static $database;

    public static function getDatabase(): Database
    {
        if (self::$database == null) {
            self::$database = (new DatabaseBuilder(""))->defaultEngine("InnoDB")->defaultCharset("utf8mb4")->defaultCollation("utf8mb4_unicode_ci")
                ->table("credential")->primary(["provider", "uid"])->foreign(["user"], "user", ["id"], ReferenceOption::CASCADE(), ReferenceOption::RESTRICT())
                ->field("provider")->type(Type::varchar(255, ""))
                ->field("uid")->type(Type::varchar(255, ""))
                ->field("user")->type(Type::int())->null()
                ->table("user")->unique(["nick"])->primary(["id"])
                ->field("id")->type(Type::int())->autoincrement()
                ->field("nick")->type(Type::varchar(100, ""))
                ->field("birthday")->type(Type::datetime())->null()
                ->field("active")->type(Type::bool())->default("0")
                ->field("gender")->type(Type::enum(["m", "f"]))->null()
                ->build();
        }
        return self::$database;
    }

    public static function initialize(\PDO $pdo, bool $keepOtherTables): void
    {
        $setting = new SqlSetting();
        $pdo->beginTransaction();
        if ($keepOtherTables) {
            foreach (self::getDatabase()->getTables() as $table) {
                $pdo->exec($table->toDropQuery($setting));
            }
        } else {
            $pdo->exec(self::getDatabase()->toDropSql($setting));
            $pdo->exec(self::getDatabase()->toCreateSql($setting));
            $pdo->exec("USE `" . self::getDatabase()->getName() . "`");
        }
        foreach (self::getDatabase()->migrate(new Database(self::getDatabase()->getName()), $setting)->getStatements() as $statement) {
            $pdo->exec($statement);
        }
        $pdo->commit();
    }
}';
        $this->assertEqualsIgnoreLineEnding($expected, $actual);
    }

    public function testEnum()
    {
        $actual = (new Enum("Gender", ['namespace' => "\mheinzerling", 'values' => ["m" => "male", "f" => "female"]]))->toPHPFile();
        $expected = "<?php
declare(strict_types = 1);

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
}";
        $this->assertEqualsIgnoreLineEnding($expected, $actual);
    }

    public function assertEqualsIgnoreLineEnding(string $expected, string $actual)
    {
        static::assertEquals(str_replace("\r", '', $expected), str_replace("\r", '', $actual));
    }
}
