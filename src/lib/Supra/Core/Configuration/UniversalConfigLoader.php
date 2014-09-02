<?php

namespace Supra\Core\Configuration;

use Supra\Core\DependencyInjection\ContainerAware;

class UniversalConfigLoader implements ContainerAware
{
    protected $container;
    
    public function setContainer(\Supra\Core\DependencyInjection\Container $container)
    {
        $this->container = $container;
    }
    
    public function load($file)
    {
        if (!is_file($file) || !is_readable($file)) {
            throw new Exception\ConfigLoaderException(
                    sprintf('File "%s" is not readable or not exists', $file)
                    );
        }
        
        $info = pathinfo($file);
        
        switch (strtolower($info['extension'])) {
            case 'yml':
                $data = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($file));
                break;
            default:
                throw new Exception\ConfigLoaderException(
                        sprintf('File "%s" is not supported', $file)
                        );
        }
        
        return $data;
    }
}