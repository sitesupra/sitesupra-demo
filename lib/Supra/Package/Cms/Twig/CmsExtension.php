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

namespace Supra\Package\Cms\Twig;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;

class CmsExtension extends \Twig_Extension implements ContainerAware
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
	 * Returns the name of the extension.
	 *
	 * @return string The extension name
	 */
	public function getName()
	{
		return 'cms';
	}

	public function getFunctions()
	{
		return array(
			new \Twig_SimpleFunction('supra_cms_css_pack', array($this, 'buildCssPack')),
			new \Twig_SimpleFunction('supra_cms_js_pack', array($this, 'buildJsPack')),
		);
	}

	public function buildCssPack()
	{
		//@todo: caching here
		return $this->container->getRouter()->generate('cms_css_pack');
	}

	public function buildJsPack()
	{
		//@todo: caching here
		return $this->container->getRouter()->generate('cms_js_pack');
	}

}