<?php

namespace Tests;

class PHPUnitUtil
{
    public static function callMethod($obj, $name, array $args)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        return $method->invokeArgs($obj, $args);
    }

    public static function getParam($obj, $name)
    {
        $class = new \ReflectionClass($obj);
        return $class->getProperty($name)->getValue($obj);
    }
}
