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

namespace Supra\Package\Cms\Pages\Editable\Transformer;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\Cms\Editable\Transformer\ValueTransformerInterface;
use Supra\Package\Cms\Entity\ReferencedElement\ImageReferencedElement;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Entity\BlockPropertyMetadata;
use Supra\Package\Cms\Pages\Editable\BlockPropertyAware;

class ImageEditorValueTransformer implements ValueTransformerInterface, BlockPropertyAware, ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @var BlockProperty
	 */
	protected $property;

	/**
	 * @param BlockProperty $blockProperty
	 */
	public function setBlockProperty(BlockProperty $blockProperty)
	{
		$this->property = $blockProperty;
	}

	public function reverseTransform($value)
	{
		if (empty($value)) {
			$this->property->getMetadata()
					->remove('image');

			return null;
		}

		$metadata = $this->property->getMetadata();

		if (! $metadata->offsetExists('image')) {
			$metadata->set('image', new BlockPropertyMetadata('image', $this->property));
		}

		$metaItem = $metadata->get('image');
		/* @var $metaItem BlockPropertyMetadata */

		$element = new ImageReferencedElement();

		// @TODO: some data validation must happen here.
		$element->fillFromArray($value);

		$metaItem->setReferencedElement($element);

		return null;
	}

	/**
	 * @param mixed $value
	 * @return null|array
	 */
	public function transform($value)
	{
		if ($value !== null) {
			// @TODO: not sure if this one is needed. just double checking.
			throw new \LogicException(
					'Expecting image containing block property value to be null.'
			);
		}

		if ($this->property->getMetadata()->offsetExists('image')) {
			
			$metaItem = $this->property->getMetadata()->get('image');

			$element = $metaItem->getReferencedElement();

			if ($element instanceof ImageReferencedElement) {

				$fileStorage = $this->container['cms.file_storage'];
				/* @var $fileStorage \Supra\Package\Cms\FileStorage\FileStorage */
				$image = $fileStorage->findImage($element->getImageId());

				if ($image !== null) {
					return array_merge(
							$element->toArray(),
							array('image' => $fileStorage->getFileInfo($image))
					);
				}
			}
		}

		return null;
	}

	/**
	 * @param ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}
	
}
