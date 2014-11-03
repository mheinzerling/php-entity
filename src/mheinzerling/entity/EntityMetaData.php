<?php
namespace mheinzerling\entity;


use mheinzerling\commons\FileUtils;
use mheinzerling\commons\StringUtils;

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
        $column .= $this->toSqlType($name, $properties);
        if (isset($properties['optional'])) $column .= ' NULL';
        else $column .= ' NOT NULL';
        if (isset($properties['default'])) $column .= ' DEFAULT \'' . $properties['default'] . '\'';
        if (isset($properties['auto'])) $column .= ' AUTO_INCREMENT';
        return $column;
    }

    private function toSqlType($name, $properties)
    {
        $type = $properties['type'];
        $length = isset($properties['length']) ? $properties['length'] : 0;
        if ($type == 'Integer') {
            $column = " INT";
            if ($length) $column .= "(" . $length . ")";
        } else if ($type == 'Double') {
            $column = " DOUBLE";
        } else if ($type == 'Boolean') {
            $column = " INT(1)";
        } else if ($type == 'String') {
            if ($length > 0 && $length <= 255) $column = " VARCHAR(" . $length . ")";
            else  $column = " TEXT";
        } else if ($type == '\DateTime') {
            if ($length > 0 && $length <= 255) $column = " VARCHAR(" . $length . ")";
            else  $column = " DATETIME";
        } else if (is_subclass_of($type, '\mheinzerling\entity\Entity')) {
            $repoclass = $type . "Repository";
            $repo = new $repoclass();
            $meta = $repo->getMeta();
            $pk = $meta['pk'];
            if (count($pk) != 1) throw new \Exception("Can't map foreign key to composed primary keys :" . implode(',', $pk));
            $p = $meta['fields'][$pk[0]];
            $forward = array();
            $forward['type'] = $p['type'];
            if (isset($p['length'])) $forward['length'] = $p['length'];
            $column = $this->toSqlType($name, $forward); //match foreign key to primary of target
        } else if (is_subclass_of($type, 'Eloquent\Enumeration\Enumeration')) {
            $v = "";
            foreach ($this->fields as $f) {
                if ($f['type'] == $type) {
                    $v = $f['values'];
                }
            }
            $values = "'" . implode("', '", array_keys($v)) . "'";
            $column = " ENUM($values)";
        } else {
            throw new \Exception("Couldn't map >" . $type . "< to a SQL"); //TODO
        }
        return $column;
    }
}