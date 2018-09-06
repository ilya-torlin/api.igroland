<?php
namespace app\components\import;

class ImporterFactory
{
    public static function create($class): ImporterInterface
    {
        $className = "\\app\\components\\import\\importer\\".$class;
        return new $className;
    }
}
