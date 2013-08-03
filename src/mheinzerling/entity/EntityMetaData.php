<?php
namespace mheinzerling\entity;


class EntityMetaData
{
    public $name;
    public $repoClass;
    public $baseClass;
    public $entityClass;
    public $namespace;
    public $table;
    public $fields;
    public $pk = array();
    public $unique = array();
    public $autoincrement;

    public function __construct(EntityRepository $repo = null)
    {

        if ($repo == null) return;

        $data = $repo->getMeta();
        $this->repoClass = $data['repoClass'];
        $this->entityClass = $data['entityClass'];
        $this->name = $data['name'];
        $this->namespace = $data['namespace'];
        $this->baseClass = $data['baseClass'];
        $this->table = $data['table'];
        $this->fields = $data['fields'];
        $this->pk = $data['pk'];
        $this->unique = $data['unique'];
        $this->autoincrement = $data['autoincrement'];
    }

    public function dropSchema()
    {
        return "DROP TABLE IF EXISTS `" . $this->table . "`;";
    }

    public function buildSchema()
    {
        $schema = 'CREATE TABLE `' . $this->table . '` (';
        foreach ($this->fields as $key => $field) {
            $schema .= $this->toSqlColumn($key, $field) . ',';

        }
        if (count($this->pk)) {
            $schema .= 'PRIMARY KEY (`' . implode('`,`', $this->pk) . '`),';
        }

        if (count($this->unique)) {
            foreach ($this->unique as $name => $fields) {
                $schema .= 'UNIQUE KEY `' . $name . '` (`' . implode('`,`', $fields) . '`),';
            }
        }

        $schema = substr($schema, 0, -1);
        $schema .= ');';
        return $schema;
    }

    private function toSqlColumn($name, $properties)
    {
        $column = "`" . $name . "`";
        $type = $properties['type'];
        $length = isset($properties['length']) ? $properties['length'] : 0;
        if ($type == 'Integer') {
            $column .= " INT";
            if ($length) $column .= "(" . $length . ")";
        } else if ($type == 'Boolean') {
            $column .= " INT(1)";
        } else if ($type == 'String') {
            if ($length > 0 && $length <= 255) $column .= " VARCHAR(" . $length . ")";
            else  $column .= " TEXT";
        } else if ($type == '\DateTime') {
            if ($length > 0 && $length <= 255) $column .= " VARCHAR(" . $length . ")";
            else  $column .= " DATETIME";
        } else if (is_subclass_of($type, '\mheinzerling\entity\Entity')) {
            $column .= " INT(11)";
        } else {

            throw new \Exception("Couldn't map >" . $type . "< to a SQL"); //TODO
        }
        if (isset($properties['optional'])) $column .= ' NULL';
        else $column .= ' NOT NULL';
        if (isset($properties['default'])) $column .= ' DEFAULT \'' . $properties['default'] . '\'';
        if (isset($properties['auto'])) $column .= ' AUTO_INCREMENT';
        return $column;
    }
}