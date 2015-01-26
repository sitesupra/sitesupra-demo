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
use Supra\Package\Cms\Entity\ReferencedElement\ImageReferencedElement;
use Supra\Package\Cms\Pages\Html\ImageTag;
use Supra\Package\Cms\Pages\Editable\BlockPropertyAware;

class ImageFilter implements FilterInterface, BlockPropertyAware, ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @var BlockProperty
	 */
	protected $blockProperty;

	public function filter($content, array $options = array())
	{
		if ($this->blockProperty->getMetadata()->offsetExists('image')) {

			$element = $this->blockProperty
					->getMetadata()
					->get('image')
					->getReferencedElement();

			if ($element !== null) {
				/* @var $element ImageReferencedElement */

				if (! $element instanceof ImageReferencedElement) {
					// @TODO: any exception should be thrown probably
					return null;
				}

				$imageId = $element->getImageId();

				$fileStorage = $this->container['cms.file_storage'];
				/* @var $fileStorage \Supra\Package\Cms\FileStorage\FileStorage */
				
				$image = $fileStorage->findImage($imageId);

				if ($image) {
					return new ImageTag($image, $fileStorage);
				}
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
	 * {@inheritDoc}
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}
}
