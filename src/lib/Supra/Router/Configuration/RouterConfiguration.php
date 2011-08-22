<?php

namespace Supra\Router\Configuration;

use Supra\Router\UriRouter;

/**
 * RouterConfiguration
 */
class RouterConfiguration
{
	/**
	 * @var string
	 */
	public $class = 'Supra\Router\UriRouter';
	
	/**
	 * @var string
	 */
	public $url = '/';
	
	/**
	 * @var string
	 */
	public $controller = 'Project\Pages\PageController';
	
	/**
	 * @return UriRouter
	 */
	public function configure()
	{
		$router = new $this->class($this->url);
		
		return $router;
	}
}