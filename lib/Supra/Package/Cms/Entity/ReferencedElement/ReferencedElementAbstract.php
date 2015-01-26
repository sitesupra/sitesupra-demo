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

namespace Supra\Package\Cms\Entity\ReferencedElement;

use Supra\Package\Cms\Entity\Abstraction\Entity;

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *		"link" = "LinkReferencedElement", 
 *		"image" = "ImageReferencedElement",
 * 		"media" = "MediaReferencedElement"
 * })
 */
abstract class ReferencedElementAbstract extends Entity
{
	/**
	 * Convert object to array
	 * @return array
	 */
	abstract public function toArray();

	/**
	 * Set properties from array
	 * @param array $array
	 */
	abstract public function fillFromArray(array $array);
	
	/**
	 * @param array $array
	 * @return ReferencedElementAbstract
	 */
	public static function fromArray(array $array)
	{
		$element = null;

		if (empty($array['type'])) {
			throw new \RuntimeException('Element type is not specified.');
		}
		
		switch ($array['type']) {
			case LinkReferencedElement::TYPE_ID:
				$element = new LinkReferencedElement();
				break;
			
			case ImageReferencedElement::TYPE_ID:
				$element = new ImageReferencedElement();
				break;

			case 'video': // @TODO: BC. Remove.
			case MediaReferencedElement::TYPE_ID:
				$element = new MediaReferencedElement();
				break;

			default:
				throw new \RuntimeException(sprintf('Unrecognized element type [%s].', $array['type']));
		}
		
		$element->fillFromArray($array);
		
		return $element;
	}
}
