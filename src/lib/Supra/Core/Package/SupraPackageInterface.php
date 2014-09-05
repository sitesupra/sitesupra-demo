<?php

namespace Supra\Core\Package;

use Supra\Core\DependencyInjection\ContainerInterface;

interface SupraPackageInterface
{
	public function boot();
	public function shutdown();
	public function inject(ContainerInterface $container);
	public function getName();
	public function finish(ContainerInterface $container);

	/**
	 * @return \Supra\Configuration\ConfigurationInterface
	 */
	public function getConfiguration();
}
