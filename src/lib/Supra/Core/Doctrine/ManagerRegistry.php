<?php

namespace Supra\Core\Doctrine;

use Doctrine\Common\Persistence\AbstractManagerRegistry;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;

class ManagerRegistry extends AbstractManagerRegistry implements ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @param \Supra\Core\DependencyInjection\ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * Fetches/creates the given services
	 *
	 * A service in this context is connection or a manager instance
	 *
	 * @param string $name name of the service
	 * @return object instance of the given service
	 */
	protected function getService($name)
	{
		if (!isset($this->container[$name])) {
			throw new \Exception(sprintf('There is no service (connection or entity manager) with id "%s"', $name));
		}

		return $this->container[$name];
	}

	/**
	 * Resets the given services
	 *
	 * A service in this context is connection or a manager instance
	 *
	 * @param string $name name of the service
	 * @return void
	 */
	protected function resetService($name)
	{
		throw new \Exception(__NAMESPACE__.__METHOD__.' is not yet implemented');
	}

	/**
	 * Resolves a registered namespace alias to the full namespace.
	 *
	 * This method looks for the alias in all registered object managers.
	 *
	 * @param string $alias The alias
	 *
	 * @return string The full namespace
	 */
	function getAliasNamespace($alias)
	{
		throw new \Exception(__NAMESPACE__.__METHOD__.' is not yet implemented');
	}

}