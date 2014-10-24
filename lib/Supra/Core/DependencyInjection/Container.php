<?php

namespace Supra\Core\DependencyInjection;

use Monolog\Logger;
use Pimple\Container as BaseContainer;
use Supra\Core\Application\ApplicationManager;
use Supra\Core\Configuration\Exception\ReferenceException;
use Supra\Core\DependencyInjection\Exception\ParameterNotFoundException;
use Supra\Core\Doctrine\ManagerRegistry;
use Supra\Core\Templating\Templating;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Kernel;

class Container extends BaseContainer implements ContainerInterface
{
	/**
	 * @var array
	 */
	protected $parameters = array();

	public function getMailer()
	{
		return $this['mailer.mailer'];
	}

	public function offsetGet($id)
	{
		$instance = parent::offsetGet($id);

		if ($instance instanceof ContainerAware) {
			$instance->setContainer($this);
		}

		return $instance;
	}

	/**
	 * @return Logger
	 */
	public function getLogger()
	{
		return $this['logger.logger'];
	}

	/**
	 * @return Kernel
	 */
	public function getKernel()
	{
		return $this['kernel.kernel'];
	}

	/**
	 * @return ManagerRegistry
	 */
	public function getDoctrine()
	{
		return $this['doctrine.doctrine'];
	}

	/**
	 * @return Session
	 */
	public function getSession()
	{
		return $this['http.session'];
	}

	/**
	 * @return Request
	 */
	public function getRequest()
	{
		return $this['http.request'];
	}

	/**
	 * @return ApplicationManager
	 */
	public function getApplicationManager()
	{
		return $this['applications.manager'];
	}

	/**
	 * @return \Supra\Core\Cache\Cache
	 */
	public function getCache()
	{
		return $this['cache.cache'];
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
		return $this['templating.templating'];
	}

	/**
	 * @return \Supra\Core\Locale\LocaleManager
	 */
	public function getLocaleManager()
	{
		return $this['locale.manager'];
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
				var_dump($value);
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
			$replacements[$expression[0]] = $this->getParameter($parameter);
		}

		return strtr($data, $replacements);
	}

	/**
	 * checks for parameter existence; only top level parameter is checked (no deep checking is performed)
	 *
	 * @param $name
	 * @return bool
	 */
	public function hasParameter($name)
	{
		return isset($this->parameters[$name]);
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
		$chunks = explode('.', $name);

		$name = $chunks[0] .
			(isset($chunks[1]) ? '.' . $chunks[1] : '');

		if (!isset($this->parameters[$name])) {
			throw new ReferenceException(sprintf('Parameter "%s" is not defined in the container', $name));
		}

		$value = $this->parameters[$name];

		if (count($chunks) > 2) {
			$path = array_slice($chunks, 2);

			while ($key = array_shift($path)) {
				if (!array_key_exists($key, $value)) {
					throw new ReferenceException(sprintf('Lost at sub-key "%s" for parameter "%s"', $key, $name));
				}

				$value = $value[$key];
			}
		}

		return $value;
	}
}

