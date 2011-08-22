<?php

namespace Supra\Loader\Configuration;

use Supra\Loader\NamespaceRecord;

class NamespaceConfiguration
{
	/**
	 * @var string
	 */
	public $class = 'Supra\Loader\NamespaceRecord';

	/**
	 * @var string
	 */
	public $namespace;
	
	/**
	 * @var string
	 */
	public $dir;
	
	/**
	 * @return NamespaceRecord
	 */
	public function configure()
	{
		$namespaceRecord = new $this->class($this->namespace, $this->dir);
		
		return $namespaceRecord;
	}
}
