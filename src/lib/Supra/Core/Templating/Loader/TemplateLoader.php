<?php

namespace Supra\Core\Templating\Loader;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Package\PackageLocator;
use Twig_Error_Loader;

class TemplateLoader implements \Twig_LoaderInterface, ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}


	/**
	 * Gets the source code of a template, given its name.
	 *
	 * @param string $name The name of the template to load
	 *
	 * @return string The template source code
	 *
	 * @throws Twig_Error_Loader When $name is not found
	 */
	public function getSource($name)
	{
		if (strpos($name, ':') === false) {
			throw new \Exception(
				sprintf('Oops, template file name does not seem to be in Package:file\name\path.html.twig format ("%s" given")', $name)
			);
		}

		list($packageName, $templatePath) = explode(':', $name);

		$path = $this->container->getApplication()->locateViewFile($packageName, $templatePath);

		return file_get_contents($path);
	}

	/**
	 * Gets the cache key to use for the cache for a given template name.
	 *
	 * @param string $name The name of the template to load
	 *
	 * @return string The cache key
	 *
	 * @throws Twig_Error_Loader When $name is not found
	 */
	public function getCacheKey($name)
	{
		return 'supra_'.md5($name);
	}

	/**
	 * Returns true if the template is still fresh.
	 *
	 * @param string $name The template name
	 * @param timestamp $time The last modification time of the cached template
	 *
	 * @return bool    true if the template is fresh, false otherwise
	 *
	 * @throws Twig_Error_Loader When $name is not found
	 */
	public function isFresh($name, $time)
	{
		throw new \Exception('fresh');
	}

}