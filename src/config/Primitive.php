<?php
namespace mheinzerling\entity\config;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * @method static Primitive STRING()
 * @method static Primitive INT()
 * @method static Primitive BOOL()
 */
final class Primitive extends AbstractEnumeration
{
    const STRING = 'string';
    const INT = 'int';
    const BOOL = 'bool';
}
