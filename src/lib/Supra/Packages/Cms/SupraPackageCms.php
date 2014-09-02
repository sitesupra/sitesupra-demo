<?php

namespace Supra\Packages\Cms;

use Supra\Core\DependencyInjection\Container;
use Supra\Core\Package\AbstractSupraPackage;
use Supra\Core\Package\PackageLocator;

class SupraPackageCms extends AbstractSupraPackage
{
    public function inject(Container $container)
    {
        //console
        $container->getConsole()->add();
        
        //routing
        $container->getRouter()->loadConfiguration(
                PackageLocator::locateConfigFile($this, 'routes.yml')
            );
    }
    
}
