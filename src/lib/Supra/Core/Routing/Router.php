<?php

namespace Supra\Core\Routing;

class Router implements \Supra\Core\DependencyInjection\ContainerAware
{
    protected $container;
    
    public function setContainer(\Supra\Core\DependencyInjection\Container $container)
    {
        $this->container = $container;
    }

    public function loadConfiguration($config)
    {
        if (!is_array($config)) {
            $config = $this->container['config.universal_loader']->load($config);
        }
        
        $processor = new \Symfony\Component\Config\Definition\Processor();
        $definition = new Configuration\RoutingConfiguration();
        
        $config = $processor->processConfiguration($definition, array($config));
        
        print_r($config);die();
    }
}