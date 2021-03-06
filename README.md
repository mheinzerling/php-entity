[![Build Status](https://travis-ci.org/mheinzerling/php-entity.svg?branch=master)](https://travis-ci.org/mheinzerling/php-entity) [![Code Climate](https://codeclimate.com/github/mheinzerling/php-entity/badges/gpa.svg)](https://codeclimate.com/github/mheinzerling/php-entity) [![Test Coverage](https://codeclimate.com/github/mheinzerling/php-entity/badges/coverage.svg)](https://codeclimate.com/github/mheinzerling/php-entity/coverage) [![Issue Count](https://codeclimate.com/github/mheinzerling/php-entity/badges/issue_count.svg)](https://codeclimate.com/github/mheinzerling/php-entity) 

#mheinzerling/entity

Simple ORM

##Composer
    "require": {
        "mheinzerling/entity": "^3.0.1"
    },
    
##Types

    Integer     -> INT (length)
    String 
     length<255 -> VARCHAR
                -> TEXT
    \DateTime   -> DATETIME
    Boolean     -> INT (1)
    Entity      -> INT(11)
    
    optional    -> NULL vs NOT NULL
    auto        -> AUTO_INCREMENT
    primary     -> default
    
##Example

See `resources/tests/entities.json` and `build.xml gen`. The parser will tell you invalid configurations.

##Changelog

### 3.0.0
- update to PHP 7.1

### 2.0.0
- update to PHP 7
- update enumeration library and symphony console
- !!! PDO toObject seems to change in 7.1; Test broken !!!

### 1.2.0
- update dependencies
- add foreign keys

### 1.1.0
- improve enums
- add double
- improve datetime mapping

### 1.0.0
initial version 