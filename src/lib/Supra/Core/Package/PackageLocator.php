<?php

namespace Supra\Core\Package;

class PackageLocator
{  
    protected static $configPath = 'config';
    
    public static function locateConfigFile($package, $name)
    {
        $path = self::locatePackageRoot($package)
                . DIRECTORY_SEPARATOR . self::$configPath
                . DIRECTORY_SEPARATOR . $name;
        
        if (!realpath($path) || !is_readable($path)) {
            throw new Exception\PackageLocatorException(
                    sprintf('Config file "%s" for package "%s" (%s) can not be resolved (expected location "%s")',
                            $name, self::formatName($package), self::formatClass($package), $path
                            )
                    );
        }
    }
    
    public static function locatePackageRoot($package)
    {
        $reflection = new \ReflectionClass($package);
        
        return dirname($reflection->getFileName());
    }
    
    public static function formatClass($package)
    {
        if (is_object($package)) {
            $package = get_class($package);
        }
        
        if (!class_exists($package)) {
            throw new Exception\PackageLocatorException(
                    sprintf('Can not resolve package class name for reference "%s"',
                            $package
                            )
                    );
        }
        
        return $package;
    }
    
    public static function formatName($package)
    {
        $class = self::formatClass($package);
        
        $class = explode('\\', $class);
        
        return $class[count($class) - 1];
    }
    
}
