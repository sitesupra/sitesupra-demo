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

use Supra\Package\Cms\Editable\Transformer\ValueTransformerInterface;
use Supra\Package\Cms\Entity\ReferencedElement\LinkReferencedElement;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Entity\BlockPropertyMetadata;
use Supra\Package\Cms\Pages\Editable\BlockPropertyAware;

class LinkEditorValueTransformer implements ValueTransformerInterface, BlockPropertyAware
{
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
					->remove('link');

			return null;
		}

		$metadata = $this->property->getMetadata();

		if (! $metadata->offsetExists('link')) {
			$metadata->set('link', new BlockPropertyMetadata('link', $this->property));
		}

		$metaItem = $metadata->get('link');
		/* @var $metaItem BlockPropertyMetadata */

		$element = new LinkReferencedElement();

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
					'Expecting link containing block property value to be null.'
			);
		}

		if ($this->property->getMetadata()->offsetExists('link')) {
			
			$metaItem = $this->property->getMetadata()->get('link');

			$element = $metaItem->getReferencedElement();

			return $element !== null ? $element->toArray() : null;
		}

		return null;
	}
	
}
