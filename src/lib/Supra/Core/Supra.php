<?php

namespace Supra\Core;

abstract class Supra
{
    /**
     *
     * @var array
     */
    protected $packages;
    
    protected $container;
    
    protected function registerPackages()
    {
        return array();
    }
    
    public function __construct()
    {
        $this->packages = $this->registerPackages();
    }
    
    public function buildContainer()
    {
        if ($this->container) {
            return $this->container;
        }
        
        //getting container instance, configuring services
        $container = new \Supra\Core\DependencyInjection\Container();
        $container['application'] = $this;

        //routing configuration
        $container['config.universal_loader'] = new \Supra\Core\Configuration\UniversalConfigLoader();
        $container['router'] = new \Supra\Core\Routing\Router();
        
        $this->buildCli($container);
        $this->injectPackages($container);
        
        return $this->container = $container;
    }
    
    public function boot()
    {
        //boot packages
        foreach ($this->getPackages() as $package)
        {
            $package->setContainer($this->container);
            $package->boot();
        }
    }
    
    public function getPackages()
    {
        return $this->packages;
    }
    
    protected function buildCli($container)
    {
        $container['console.application'] = new \Supra\Core\Console\Application();
    }
    
    protected function injectPackages($container)
    {
        foreach ($this->getPackages() as $package) {
            $package->inject($container);
        }
    }
}