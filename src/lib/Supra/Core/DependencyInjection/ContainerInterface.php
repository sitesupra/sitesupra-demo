<?php

namespace Supra\Core\DependencyInjection;

use Supra\Core\Templating\Templating;

interface ContainerInterface
{
	/**
	 * Getter for Router instance
	 *
	 * @return \Supra\Core\Routing\Router
	 */
	public function getRouter();

	/**
	 * Getter for CLI app
	 *
	 * @return \Supra\Core\Console\Application
	 */
	public function getConsole();

	/**
	 * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
	 */
	public function getEventDispatcher();

	/**
	 * @return \Symfony\Component\Security\Core\SecurityContext
	 */
	public function getSecurityContext();

	/**
	 * @return \Supra\Core\Supra
	 */
	public function getApplication();

	/**
	 * Sets parameter
	 *
	 * @param $name
	 * @param $value
	 */
	public function setParameter($name, $value);

	/**
	 * Gets parameter by name
	 *
	 * @param $name
	 * @return mixed
	 */
	public function getParameter($name);

	/**
	 * Gets names of all parameters defined
	 *
	 * @return array
	 */
	public function getParameters();

	/**
	 * checks for parameter existence
	 *
	 * @param $name
	 * @return bool
	 */
	public function hasParameter($name);

	/**
	 * Replaces %param.name% -> param.value in a given array
	 *
	 * @param array $data
	 * @return array|string
	 */
	public function replaceParameters($data);

	/**
	 * Replaces %param.name% -> param.value in a given string
	 *
	 * @param string $data
	 * @return string
	 * @throws \Supra\Core\Configuration\Exception\ReferenceException
	 */
	public function replaceParametersScalar($data);

	/**
	 * Returns current templating implementation
	 *
	 * @return Templating
	 */
	public function getTemplating();
}