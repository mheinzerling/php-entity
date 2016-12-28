<?php
declare(strict_types = 1);

namespace mheinzerling\entity\config;

use mheinzerling\commons\database\structure\builder\DatabaseBuilder;
use mheinzerling\commons\database\structure\Database;
use mheinzerling\commons\database\structure\SqlSetting;

class ConfigTest extends \PHPUnit_Framework_TestCase
{

    public function testLoad()
    {
        $config = Config::loadFile(realpath(__DIR__ . "../../..") . "/resources/tests/entities.json");
        var_dump($config); //TODO assert and parse errors
    }

    public function testToDatabase()
    {
        $config = Config::loadFile(realpath(__DIR__ . "../../..") . "/resources/tests/entities.json");
        $builder = new DatabaseBuilder("");
        $config->addTo($builder);
        $database = $builder->build();
        $expected['500.0'] = str_replace("\r", "", "CREATE TABLE IF NOT EXISTS `user` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nick` VARCHAR(100) NOT NULL,
  `birthday` DATETIME DEFAULT NULL,
  `active` BOOL NOT NULL DEFAULT '0',
  `gender` ENUM('m', 'f') DEFAULT NULL,
  UNIQUE KEY `uni_user_nick` (`nick`),
  PRIMARY KEY (`id`)
  );");
        $expected['500.1'] = str_replace("\r", "", "CREATE TABLE IF NOT EXISTS `credential` (
  `provider` VARCHAR(255) NOT NULL,
  `uid` VARCHAR(255) NOT NULL,
  `user` INT DEFAULT NULL,
  PRIMARY KEY (`provider`, `uid`),
  CONSTRAINT `fk_credential_user__user_id` FOREIGN KEY (`user`) REFERENCES `user` (`id`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
  );");
        static::assertEquals($expected, $database->migrate(new Database(""), new SqlSetting())->getStatements());

    }

}