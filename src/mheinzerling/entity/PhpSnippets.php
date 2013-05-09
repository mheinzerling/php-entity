<?php

namespace mheinzerling\entity;


use mheinzerling\commons\StringUtils;

class PhpSnippets
{
    public static function repository($name, $namespace)
    {
        $result = self::header($namespace);
        $result .= "class " . $name . "Repository extends Base" . $name . "Repository\n";
        $result .= "{\n";
        $result .= "}";
        return $result;
    }

    public static function entity($name, $namespace)
    {
        $result = self::header($namespace);
        $result .= "class " . $name . " extends Base" . $name . "\n";
        $result .= "{\n";
        $result .= "}";
        return $result;
    }

    private static function header($namespace)
    {
        $result = "<?php\n";
        if (!empty($namespace)) $result .= "namespace $namespace;\n\n";
        return $result;
    }

    public static function baserepository($name, $properties)
    {
        $ns = isset($properties['namespace']) ? $properties['namespace'] : "";
        $fields = '';
        $auto = null;
        $pk = array();
        foreach ($properties as $field => $property) {
            if ($field == 'namespace') continue;
            if ($field == 'unique') continue; //TODO
            if (isset($property['primary']) && $property['primary']) $pk[] = $field;
            if (isset($property['auto']) && $property['auto']) {
                $auto = $field;
            }
            $p = '';
            foreach ($property as $key => $value) {
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                } else if (is_string($value)) {
                    $value = "'" . $value . "'";
                }
                $p .= "'$key' => " . $value . ", ";
            }
            $p = substr($p, 0, -2);
            $fields .= "                '$field' => array(" . $p . "),\n";
        }
        $fields = substr($fields, 0, -2);

        $result = self::header($ns);
        $result .= "use mheinzerling\\entity\\EntityRepository;\n\n";
        $result .= "class Base" . $name . "Repository extends EntityRepository\n";
        $result .= "{\n";
        $result .= "    public function getMeta()\n";
        $result .= "    {\n";

        $result .= "        return array(\n";
        $result .= "            'name' => '" . $name . "',\n";
        $result .= "            'repoClass' => '" . $ns . "\\" . $name . "Repository',\n";
        $result .= "            'baseClass' => '" . $ns . "\\Base" . $name . "',\n";
        $result .= "            'entityClass' => '" . $ns . "\\" . $name . "',\n";
        $result .= "            'namespace' => '" . $ns . "',\n";
        $result .= "            'table' => '" . strtolower($name) . "',\n";
        $result .= "            'fields' => array(\n";
        $result .= "" . $fields . "),\n";
        $result .= "            'pk' => array('" . implode("', '", $pk) . "'),\n";
        $result .= "            'autoincrement' => " . ($auto == null ? "null" : "'$auto'") . "\n";
        $result .= "        );\n";
        $result .= "    }\n";

        if (count($pk) > 0) {
            $upk = array_map('mheinzerling\commons\StringUtils::firstCharToUpper', $pk);
            $where = array_map(function ($n) {
                return "`$n`='\" . \$$n . \"'";
            }, $pk);
            $result .= "\n";
            $result .= "    /**\n";
            $result .= "     * @return $name\n";
            $result .= "     */\n";
            $result .= "    public function fetchBy" . implode("And", $upk) . "(\$" . implode(", \$", $pk) . ")\n";
            $result .= "    {\n";
            $result .= "        return \$this->fetchUnique(\"WHERE " . implode(" AND ", $where) . "\");\n";
            $result .= "    }\n";
        }

        $result .= "}";
        return $result;
    }

    public static function base($name, $properties, $foreignKeys)
    {
        $namespace = isset($properties['namespace']) ? $properties['namespace'] : "";

        $vars = array();
        $special = array();
        $requireProxy = false;
        foreach ($properties as $field => $property) {
            if ($field == 'namespace') continue;
            if ($field == 'unique') continue; //TODO
            $type = $property['type']; //TODO?
            $vars[$field] = $type;
            if ($type[0] == '\\') {
                $special[$field] = $type; //TODO?
                $requireProxy |= $type != '\DateTime'; //TODO?
            }
        }

        $result = self::header($namespace);
        $result .= "use mheinzerling\\entity\\Entity;\n";
        if ($requireProxy) $result .= "use mheinzerling\\entity\\EntityProxy;\n";
        $result .= "\n";
        $result .= "abstract class Base$name extends Entity\n";
        $result .= "{\n";

        foreach ($vars as $field => $type) {
            $result .= "    /**\n";
            $result .= "     * @var $type\n";
            $result .= "     */\n";
            $result .= "    protected $$field;\n\n";
        }

        if (count($special) > 0) {
            $result .= "    public function __construct()\n";
            $result .= "    {\n";
            foreach ($special as $field => $type) {
                $result .= "        if (!\$this->$field instanceof $type && \$this->$field != null) {\n";
                if ($type == '\DateTime') {
                    $result .= "            \$this->$field = new \DateTime(\$this->$field);\n";
                } else {
                    //Entity
                    $result .= "            \$this->$field = new EntityProxy('" . $type . "Repository', array('" . $foreignKeys[$type] . "' => \$this->$field));\n";
                }
                $result .= "        }\n";

            }


            $result .= "    }\n\n";
        }

        foreach ($vars as $field => $type) {
            $uField = StringUtils::firstCharToUpper($field);
            $result .= "    /**\n";
            $result .= "     * @param $type $$field\n";
            $result .= "     */\n";
            $result .= "    public function set$uField($$field)\n";
            $result .= "    {\n";
            $result .= "        \$this->$field = $$field;\n";
            $result .= "    }\n";
            $result .= "\n";
            $result .= "    /**\n";
            $result .= "     * @return $type\n";
            $result .= "     */\n";
            $result .= "    public function get$uField()\n";
            $result .= "    {\n";
            $result .= "        return \$this->$field;\n";
            $result .= "    }\n\n";
        }


        $result .= "}";
        return $result;


    }

}