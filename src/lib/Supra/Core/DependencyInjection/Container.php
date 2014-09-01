<?php

namespace Supra\Core\DependencyInjection;

use Pimple\Container as BaseContainer;

class Container extends BaseContainer
{
    public function offsetGet($id)
    {
        $instance = parent::offsetGet($id);
        
        if ($instance instanceof ContainerAware) {
            $instance->setContainer($this);
        }
        
        return $instance;
    }

}
