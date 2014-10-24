<?php

namespace Supra\Core\DependencyInjection;

use Monolog\Logger;
use Supra\Core\Application\ApplicationManager;
use Supra\Core\Cache\Cache;
use Supra\Core\Doctrine\ManagerRegistry;
use Supra\Core\Templating\Templating;
use Swift_Mailer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Kernel;

interface ContainerInterface
{
	/**
	 * @return Swift_Mailer
	 */
	public function getMailer();

	/**
	 * @return Logger
	 */
	public function getLogger();

	/**
	 * @return Kernel
	 */
	public function getKernel();

	/**
	 * @return ManagerRegistry
	 */
	public function getDoctrine();

	/**
	 * @return Session
	 */
	public function getSession();

	/**
	 * @return Request
	 */
	public function getRequest();

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
	 * @return \Supra\Core\Locale\LocaleManager
	 */
	public function getLocaleManager();

	/**
	 * @return \Supra\Core\Supra
	 */
	public function getApplication();

	/**
	 * @return Cache
	 */
	public function getCache();

	/**
	 * @return ApplicationManager
	 */
	public function getApplicationManager();

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