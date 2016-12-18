<?php
declare(strict_types = 1);
namespace mheinzerling\entity\generator;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * @method static Primitive STRING()
 * @method static Primitive INT()
 * @method static Primitive BOOL()
 * @method static Primitive VOID()
 * @method static Primitive MIXED()
 * @method static Primitive ARRAY()
 */
final class Primitive extends AbstractEnumeration
{
    const STRING = 'string';
    const INT = 'int';
    const BOOL = 'bool';
    const VOID = 'void';
    const MIXED = 'mixed';
    const ARRAY = 'array';
}
