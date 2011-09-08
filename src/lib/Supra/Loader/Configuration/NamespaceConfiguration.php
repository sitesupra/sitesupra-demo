<?php

namespace Supra\Loader\Configuration;

use Supra\Loader\Strategy\LoaderStrategyInterface;

class NamespaceConfiguration
{
	/**
	 * @var string
	 */
	public $class = 'Supra\Loader\Strategy\NamespaceLoaderStrategy';

	/**
	 * @var string
	 */
	public $namespace;
	
	/**
	 * @var string
	 */
	public $dir;
	
	/**
	 * @return LoaderStrategyInterface
	 */
	public function configure()
	{
		$strategy = new $this->class($this->namespace, $this->dir);
		
		return $strategy;
	}
}
