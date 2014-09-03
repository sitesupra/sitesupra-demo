<?php

namespace Supra\Core\DependencyInjection;

use Pimple\Container as BaseContainer;

class Container extends BaseContainer implements ContainerInterface
{
	public function offsetGet($id)
	{
		$instance = parent::offsetGet($id);

		if ($instance instanceof ContainerAware) {
			$instance->setContainer($this);
		}

		return $instance;
	}

	/**
	 * Getter for Router instance
	 *
	 * @return \Supra\Core\Routing\Router
	 */
	public function getRouter()
	{
		return $this['router'];
	}

	/**
	 * Getter for CLI app
	 *
	 * @return \Supra\Core\Console\Application
	 */
	public function getConsole()
	{
		return $this['console.application'];
	}
}

