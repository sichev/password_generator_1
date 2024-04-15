<?php

namespace Tests;

class PHPUnitUtil
{
    public static function callMethod($obj, $name, array $args)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
// $method->setAccessible(true); // Use this if you are running PHP older than 8.1.0
        return $method->invokeArgs($obj, $args);
    }
}
