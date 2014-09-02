<?php

namespace Supra\Core\Routing;


class Router
{
    public function loadConfiguration($configFile)
    {
        $yml = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($configFile));
        
        $processor = new \Symfony\Component\Config\Definition\Processor();
        $definition = new Configuration\RoutingConfiguration();
        
        $config = $processor->processConfiguration($definition, array($yml));
        
        print_r($config);die();
    }
}