<?php
declare(strict_types = 1);

namespace mheinzerling\entity\config;


use mheinzerling\commons\database\structure\builder\DatabaseBuilder;
use mheinzerling\commons\database\structure\Database;
use mheinzerling\commons\database\structure\index\ReferenceOption;
use mheinzerling\commons\database\structure\SqlSetting;
use mheinzerling\commons\database\structure\type\Type;
use mheinzerling\commons\FileUtils;
use mheinzerling\commons\JsonUtils;
use mheinzerling\commons\Separator;
use mheinzerling\meta\language\AClass;
use mheinzerling\meta\language\Primitive;
use mheinzerling\meta\writer\ClassWriter;

class Config
{
    /**
     * @var string
     */
    private $gensrc;
    /**
     * @var string
     */
    private $src;
    /**
     * @var AClass
     */
    private $modelClass;

    /**
     * @var Entity[]
     */
    private $entities = [];

    /**
     * @var Enum[]
     */
    private $enums = [];


    private function __construct(array $json)
    {
        JsonUtils::validProperties($json, ["gensrc", "src", "model", "entities", "enums"]);
        $this->gensrc = JsonUtils::required($json, 'gensrc');
        $this->src = JsonUtils::required($json, 'src');
        $this->modelClass = AClass::absolute(JsonUtils::required($json, 'model'));
        foreach (JsonUtils::optional($json, 'entities', []) as $name => $e) $this->entities[$name] = new Entity($name, $this->modelClass, $e);
        foreach (JsonUtils::optional($json, 'enums', []) as $name => $e) $this->enums[$name] = new Enum($name, $e);

        foreach ($this->entities as $entity) {
            $entity->resolveLazyTypes($this->entities, $this->enums);
        }
    }

    public static function loadFile(string $file): Config
    {
        return self::loadJson(file_get_contents($file));
    }

    public static function loadJson(string $jsonString): Config
    {
        $json = JsonUtils::parseToArray($jsonString);
        return new Config($json);
    }


    public function addTo(DatabaseBuilder $databaseBuilder)
    {
        foreach ($this->entities as $entity) {
            $entity->addTo($databaseBuilder);
        }
    }

    /**
     * @return Entity[]
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    /**
     * @return Enum[]
     */
    public function getEnums(): array
    {
        return $this->enums;
    }

    public function toModelPHPFile(): string
    {
        //TODO 4 new properties
        $classWriter = (new ClassWriter($this->modelClass->simple()))->namespace($this->modelClass->getNamespace());

        $classWriter->use(AClass::absolute(DatabaseBuilder::class));
        $classWriter->use(AClass::absolute(ReferenceOption::class));
        $classWriter->use(AClass::absolute(Type::class));

        $classWriter->field("database")->private()->static()->class(AClass::absolute(Database::class));
        $methodWriter = $classWriter->method("getDatabase")->public()->static()->returnClass(AClass::absolute(Database::class));
        $dbBuilder = new DatabaseBuilder("");
        $this->addTo($dbBuilder);
        $builderCode = $dbBuilder->build()->toBuilderCode("InnoDB", "utf8mb4", "utf8mb4_unicode_ci");
        $methodWriter->line("if (self::\$database == null) {");
        $methodWriter->line("    self::\$database = " . str_replace("\n", "\n            ", $builderCode . ";"));
        $methodWriter->line("}");
        $methodWriter->line("return self::\$database;");


        $classWriter->use(AClass::absolute(SqlSetting::class));
        $methodWriter = $classWriter->method("initialize")->public()->static()
            ->paramClass("pdo", AClass::absolute(\PDO::class))
            ->paramPrimitive("keepOtherTables", Primitive::BOOL(), true)
            ->void();
        $methodWriter->line('$setting = new SqlSetting();');
        $methodWriter->line('$pdo->beginTransaction();');
        $methodWriter->line('if ($keepOtherTables) {');
        $methodWriter->line('    foreach (self::getDatabase()->getTables() as $table) {');
        $methodWriter->line('        $pdo->exec($table->toDropQuery($setting));');
        $methodWriter->line('    }');
        $methodWriter->line('} else {');
        $methodWriter->line('    $pdo->exec(self::getDatabase()->toDropSql($setting));');
        $methodWriter->line('    $pdo->exec(self::getDatabase()->toCreateSql($setting));');
        $methodWriter->line('    $pdo->exec("USE `" . self::getDatabase()->getName() . "`");');
        $methodWriter->line('}');
        $methodWriter->line('foreach (self::getDatabase()->migrate(new Database(self::getDatabase()->getName()), $setting)->getStatements() as $statement) {');
        $methodWriter->line('    $pdo->exec($statement);');
        $methodWriter->line('}');
        $methodWriter->line('$pdo->commit();');
        return $methodWriter->write();
    }


    public function generateFiles(): array
    {
        $files = [];
        //TODO PSR 0/4
        foreach ($this->entities as $e) {
            $files += $e->generateFiles($this->src, $this->gensrc);
        }

        foreach ($this->enums as $e) {
            $files += $e->generateFiles($this->src);
        }

        $gensrc = FileUtils::to(FileUtils::append($this->gensrc, $this->modelClass->getNamespace()->fullyQualified()), Separator::UNIX());
        $files[FileUtils::append($gensrc, ucfirst($this->modelClass->simple()) . ".php")] = ["content" => $this->toModelPHPFile(), 'overwrite' => true];
        return $files;
    }


}