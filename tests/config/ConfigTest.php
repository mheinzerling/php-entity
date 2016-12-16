<?php
declare(strict_types = 1);

namespace mheinzerling\entity\config;

use mheinzerling\commons\database\structure\builder\DatabaseBuilder;
use mheinzerling\commons\database\structure\SqlSetting;

class ConfigTest extends \PHPUnit_Framework_TestCase
{

    public function testLoad()
    {
        $config = Config::load(realpath(__DIR__ . "../../..") . "/resources/tests/entities.json");
        var_dump($config); //TODO assert and parse errors
    }

    public function testToDatabase()
    {
        $config = Config::load(realpath(__DIR__ . "../../..") . "/resources/tests/entities.json");
        $builder = new DatabaseBuilder("");
        $config->addTo($builder);
        $database = $builder->build();
        $expected = "CREATE TABLE IF NOT EXISTS `User` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nick` VARCHAR(100) NOT NULL,
  `birthday` DATETIME DEFAULT NULL,
  `active` BOOL NOT NULL DEFAULT '0',
  `gender` ENUM('m', 'f') DEFAULT NULL,
  UNIQUE KEY `uni_User_nick` (`nick`),
  PRIMARY KEY (`id`)
  );

CREATE TABLE IF NOT EXISTS `Credential` (
  `provider` VARCHAR(255) NOT NULL,
  `uid` VARCHAR(255) NOT NULL,
  `user` INT DEFAULT NULL,
  PRIMARY KEY (`provider`, `uid`),
  CONSTRAINT `fk_Credential_user__User_id` FOREIGN KEY (`user`) REFERENCES `User` (`id`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
  );";
        static::assertEquals(str_replace("\r", "", $expected), $database->toCreateSql(new SqlSetting()));

    }

}