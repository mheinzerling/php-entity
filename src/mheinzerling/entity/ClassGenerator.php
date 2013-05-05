<?php

namespace mheinzerling\entity;


use mheinzerling\commons\FileUtils;
use mheinzerling\commons\StringUtils;

class ClassGenerator
{
    private $config;
    private $src;
    private $gensrc;

    public function __construct(array $jsonConfig)
    {
        $this->config = $jsonConfig;
        $this->src = isset($this->config['src']) ? $this->config['src'] : "src";
        $this->gensrc = isset($this->config['gensrc']) ? $this->config['gensrc'] : "gensrc";
    }

    public function generateFiles()
    {
        $files = array();
        foreach ($this->config['entities'] as $name => $properties) {
            $name = StringUtils::firstCharToUpper($name);
            $ns = isset($properties['namespace']) ? $properties['namespace'] : "";
            $src = FileUtils::toUnix(FileUtils::append($this->src, $ns));
            $gensrc = FileUtils::toUnix(FileUtils::append($this->gensrc, $ns));

            $files[FileUtils::append($src, $name . ".php")] = PhpSnippets::entity($name, $ns);
            $files[FileUtils::append($src, $name . "Repository.php")] = PhpSnippets::repository($name, $ns);
            $foreignKeys = $this->validate($properties);
            $files[FileUtils::append($gensrc, $name . "MetaData.php")] = PhpSnippets::metadata($name, $properties);
            $files[FileUtils::append($gensrc, "Base" . $name . ".php")] = PhpSnippets::base($name, $properties, $foreignKeys);

        }
        return $files;
    }

    private function validate(&$properties)
    {
        //TODO
//else throw new AnnotationException("Multiple autoincrement values in " . $this->entityClass);
        //resolve entity name spaces
        return array();
    }
}