<?php

namespace Supra\Core\Console;

use Supra\Core\DependencyInjection\Container;
use Supra\Core\DependencyInjection\ContainerAware;
use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication implements ContainerAware
{
    
    protected $container;
    
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }
}
