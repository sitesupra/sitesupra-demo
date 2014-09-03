<?php

namespace Supra\Core\DependencyInjection;

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
}