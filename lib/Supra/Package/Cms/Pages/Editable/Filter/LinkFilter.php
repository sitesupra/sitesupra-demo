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

namespace Supra\Package\Cms\Pages\Editable\Filter;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\Cms\Editable\Filter\FilterInterface;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Entity\ReferencedElement\ReferencedElementUtils;
use Supra\Package\Cms\Entity\ReferencedElement\LinkReferencedElement;
use Supra\Package\Cms\Html\HtmlTag;
use Supra\Package\Cms\Pages\Editable\BlockPropertyAware;

class LinkFilter implements FilterInterface, BlockPropertyAware, ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @var BlockProperty
	 */
	protected $blockProperty;

	/**
	 * {@inheritDoc}
	 */
	public function filter($content, array $options = array())
	{
		if ($this->blockProperty->getMetadata()->offsetExists('link')) {

			$element = $this->blockProperty
					->getMetadata()
					->get('link')
					->getReferencedElement();

			if ($element !== null) {
				/* @var $element LinkReferencedElement */

				if (! $element instanceof LinkReferencedElement) {
					// @TODO: any exception should be thrown probably
					return null;
				}

				// @TODO: the same code is inside HtmlFilter, should combine somehow.

				$title = ReferencedElementUtils::getLinkReferencedElementTitle(
						$element,
						$this->container->getDoctrine()->getManager(),
						$this->container->getLocaleManager()->getCurrentLocale()
				);

				// @TODO: what if we failed to obtain the URL?
				$url = ReferencedElementUtils::getLinkReferencedElementUrl(
						$element,
						$this->container->getDoctrine()->getManager(),
						$this->container->getLocaleManager()->getCurrentLocale()
				);

				$tag = new HtmlTag('a', $title ? $title : $url);

				$tag->setAttribute('title', $title)
						->setAttribute('href', $url)
						->setAttribute('class', $element->getClassName())
				;

				if (($target = $element->getTarget()) !== null) {
					$tag->setAttribute('target', $target);
				}

				switch ($element->getResource()) {
					case LinkReferencedElement::RESOURCE_FILE:
						$tag->setAttribute('target', '_blank');
						break;
				}

				return $tag;
			}
		}

		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setBlockProperty(BlockProperty $blockProperty)
	{
		$this->blockProperty = $blockProperty;
	}

	/**
	 * @param ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}
}
