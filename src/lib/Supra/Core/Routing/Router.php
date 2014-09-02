<?php

namespace Supra\Core\Routing;

use \Symfony\Component\Routing\RouteCollection;

class Router implements \Supra\Core\DependencyInjection\ContainerAware
{
    protected $container;
    
    /**
     * Application routes
     * 
     * @var RouteCollection
     */
    protected $routes;
    
    public function __construct()
    {
        $this->routes = new RouteCollection();
    }
    
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
        
        foreach ($config['routes'] as $name => $routeParams) {
            $route = new \Symfony\Component\Routing\Route(
                    $config['configuration']['prefix'] . $routeParams['pattern'],
                        array_merge(
                            $config['configuration']['defaults'],
                            $routeParams['defaults'],
                            array('controller' => $routeParams['controller'])
                            )
                    );
            
            $this->routes->add($name, $route);
        }
        
        print_r($this->routes);die();
    }
}