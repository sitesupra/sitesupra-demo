<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace Supra\Package\Framework\Twig;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class FrameworkExtension extends \Twig_Extension implements ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	public function getFunctions()
	{
		return array(
			new \Twig_SimpleFunction('supra_path', array($this, 'getSupraPath')),
			new \Twig_SimpleFunction('controller', array($this, 'renderController'), array('is_safe' => array('html')))
		);
	}

	public function getFilters()
	{
		return array(
			new \Twig_SimpleFilter('dateintl', array($this, 'filterLocalizedDatePattern'))
		);
	}

	public function filterLocalizedDatePattern($date, $pattern, $locale = null)
	{
		if (!class_exists('IntlDateFormatter')) {
			throw new \RuntimeException('The intl extension is needed to use intl-based filters.');
		}

		$formatter = \IntlDateFormatter::create(
			$locale !== null ? $locale : \Locale::getDefault(),
			\IntlDateFormatter::NONE,
			\IntlDateFormatter::NONE,
			date_default_timezone_get()
		);

		$formatter->setPattern($pattern);

		if (!$date instanceof \DateTime) {
			if (\ctype_digit((string) $date)) {
				$date = new \DateTime('@'.$date);
				$date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
			} else {
				$date = new \DateTime($date);
			}
		}

		return $formatter->format($date->getTimestamp());
	}

	public function getSupraPath($name, $params = array())
	{
		return $this->container->getRouter()->generate($name, $params);
	}

	public function renderController($name)
	{
		//@todo: parameters support
		$configuration = $this->container->getApplication()->parseControllerName($name);

		$request = new Request();
		$request->attributes->add(array(
			'_controller' => $configuration['controller'],
			'_action' => $configuration['action']
		));

		return $this->container->getKernel()->handle($request)->getContent();
	}

	/**
	 * Returns the name of the extension.
	 *
	 * @return string The extension name
	 */
	public function getName()
	{
		return 'framework';
	}

}
