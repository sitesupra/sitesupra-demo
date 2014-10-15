<?php

namespace Supra\Core\Doctrine\Logger;

use Doctrine\DBAL\Logging\SQLLogger;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;

class DebugLogger implements  SQLLogger, ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @param ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * Logs a SQL statement somewhere.
	 *
	 * @param string $sql The SQL to be executed.
	 * @param array $params The SQL parameters.
	 * @param array $types The SQL parameter types.
	 * @return void
	 */
	public function startQuery($sql, array $params = null, array $types = null)
	{
		$this->container->getLogger()->addDebug(sprintf('DOCTRINE: %s', $sql), $params);
	}

	/**
	 * Mark the last started query as stopped. This can be used for timing of queries.
	 *
	 * @return void
	 */
	public function stopQuery()
	{
	}

}