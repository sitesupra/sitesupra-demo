<?php

namespace Supra\Core\Package;

use Supra\Core\DependencyInjection\ContainerInterface;

interface SupraPackageInterface
{
	public function boot();
	public function shutdown();
	public function inject(ContainerInterface $container);
}
