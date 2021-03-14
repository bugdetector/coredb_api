<?php

namespace Src\Entity;

use CoreDB\Kernel\Database\DataType\ShortText;
use CoreDB\Kernel\TableMapper;

/**
 * Object relation with table logins
 * @author murat
 */

class Logins extends TableMapper
{
    public ShortText $ip_address;
    public ShortText $username;

    /**
     * @inheritdoc
     */
    public static function getTableName(): string
    {
        return "logins";
    }
}