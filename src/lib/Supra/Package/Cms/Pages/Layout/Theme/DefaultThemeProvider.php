<?php

namespace Supra\Package\Cms\Pages\Layout\Theme;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;

class DefaultThemeProvider implements ThemeProviderInterface, ContainerAware
{
	/**
	 * @var ThemeInterface[]
	 */
	protected $themes = array();

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @param ThemeInterface $theme
	 * @throws \RuntimeException
	 */
	public function registerTheme(ThemeInterface $theme)
	{
		$themeName = $theme->getName();

		if (isset($this->themes[$themeName])) {
			throw new \RuntimeException(
					"Theme [{$themeName}] is already in collection."
			);
		}

		$this->themes[$themeName] = $theme;
	}

	/**
	 * @return ThemeInterface
	 */
	public function getActiveTheme()
	{
		if (! $this->container->hasParameter('cms.active_theme')) {
			throw new \RuntimeException('No active theme set.');
		}

		$themeName = $this->container->getParameter('cms.active_theme');

		if (! isset($this->themes[$themeName])) {
			throw new \InvalidArgumentException("There is no theme [$themeName].");
		}

		return $this->themes[$themeName];
	}

	/**
	 * @param ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}
}
