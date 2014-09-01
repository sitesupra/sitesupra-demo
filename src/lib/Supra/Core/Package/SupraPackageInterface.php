<?php

namespace Supra\Core\Package;

use Supra\Core\DependencyInjection\Container;

interface SupraPackageInterface
{
    public function boot();
    public function shutdown();
    public function inject(Container $container);
}
