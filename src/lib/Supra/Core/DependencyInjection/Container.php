<?php

namespace Supra\Core\DependencyInjection;

use Pimple\Container as BaseContainer;
use Supra\Core\Configuration\Exception\ReferenceException;
use Supra\Core\DependencyInjection\Exception\ParameterNotFoundException;
use Supra\Core\Templating\Templating;

class Container extends BaseContainer implements ContainerInterface
{
	/**
	 * @var array
	 */
	protected $parameters = array();

	public function offsetGet($id)
	{
		$instance = parent::offsetGet($id);

		if ($instance instanceof ContainerAware) {
			$instance->setContainer($this);
		}

		return $instance;
	}

	/**
	 * @return \Supra\Core\Supra
	 */
	public function getApplication()
	{
		return $this['application'];
	}

	/**
	 * Getter for Router instance
	 *
	 * @return \Supra\Core\Routing\Router
	 */
	public function getRouter()
	{
		return $this['routing.router'];
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

	/**
	 * Getter for event dispatcher
	 *
	 * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
	 */
	public function getEventDispatcher()
	{
		return $this['event.dispatcher'];
	}

	/**
	 * @return \Symfony\Component\Security\Core\SecurityContext
	 */
	public function getSecurityContext()
	{
		return $this['security.context'];
	}

	/**
	 * Returns current templating implementation
	 *
	 * @return Templating
	 */
	public function getTemplating()
	{
		return $this['templating'];
	}

	/**
	 * Sets parameter
	 *
	 * @param $name
	 * @param $value
	 */
	public function setParameter($name, $value)
	{
		$this->parameters[$name] = $value;
	}

	/**
	 * Gets parameter by name
	 *
	 * @param $name
	 * @throws Exception\ParameterNotFoundException
	 * @return mixed
	 */
	public function getParameter($name)
	{
		if (!$this->hasParameter($name)) {
			throw new ParameterNotFoundException(sprintf('Parameter "%s" is not defined in the container', $name));
		}

		return $this->parameters[$name];
	}

	/**
	 * checks for parameter existence
	 *
	 * @param $name
	 * @return bool
	 */
	public function hasParameter($name)
	{
		return isset($this->parameters[$name]);
	}

	/**
	 * Gets names of all parameters defined
	 *
	 * @return array
	 */
	public function getParameters()
	{
		return array_keys($this->parameters);
	}

	/**
	 * Replaces %param.name% -> param.value in a given array
	 *
	 * @param array $data
	 * @return array|string
	 */
	public function replaceParameters($data)
	{
		//@todo: this mostly repeats core from Supra::buildContainer(). Should we merge it?
		if (is_string($data)) {
			return $this->replaceParametersScalar($data);
		}

		$obj = $this;

		array_walk_recursive($data, function (&$value) use ($obj) {
			if (is_string($value)) {
				$value = $obj->replaceParametersScalar($value);
			}
		});

		return $data;
	}

	/**
	 * Replaces %param.name% -> param.value in a given string
	 *
	 * @param string $data
	 * @return string
	 * @throws \Supra\Core\Configuration\Exception\ReferenceException
	 */
	public function replaceParametersScalar($data)
	{
		$count = preg_match_all('/%[a-z\\._]+%/i', $data, $matches);

		if (!$count) {
			return $data;
		}

		$replacements = array();

		foreach ($matches as $expression) {
			$parameter = trim($expression[0], '%');
			if (!$this->hasParameter($parameter)) {
				throw new ReferenceException('Parameter "%s" can not be resolved', $parameter);
			}
			$replacements[$expression[0]] = $this->getParameter($parameter);
		}

		return strtr($data, $replacements);
	}
}

