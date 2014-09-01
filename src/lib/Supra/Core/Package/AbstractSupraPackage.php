<?php

namespace Supra\Core\Package;

use Supra\Core\DependencyInjection\Container;
use Supra\Core\DependencyInjection\ContainerAware;

abstract class AbstractSupraPackage implements SupraPackageInterface, ContainerAware
{
    /**
     * Dependency injection container
     * 
     * @var Container
     */
    protected $container;
    
    public function boot()
    {
        
    }
    
    public function inject(Container $container)
    {
        
    }
    
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    public function shutdown()
    {
        
    }
}
