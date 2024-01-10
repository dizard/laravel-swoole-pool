<?php

namespace la\ConnectionManager;

class RedisManager extends \Illuminate\Redis\RedisManager
{
    public function connection($name = null)
    {
        $connection =  parent::connection($name);
        var_dump(spl_object_id($connection));
        return $connection;
    }

    public function purge($name = null)
    {
        return parent::purge($name);
    }
}
