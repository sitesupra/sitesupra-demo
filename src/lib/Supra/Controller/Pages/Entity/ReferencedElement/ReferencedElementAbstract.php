<?php

namespace Supra\Controller\Pages\Entity\ReferencedElement;

use Supra\Controller\Pages\Entity\Abstraction\Entity;
use Supra\Controller\Pages\Exception;

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *		"link" = "LinkReferencedElement", 
 *		"image" = "ImageReferencedElement"
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
	 * @FIXME: should move to CMS
	 * @param array $array
	 */
	public static function fromArray(array $array, $caller)
	{
		$element = null;
		
		switch ($array['type']) {
			case 'link':
				$element = new LinkReferencedElement();
				$element->fillArray($array);
				break;
			case 'image':
				$element = new ImageReferencedElement();
				$element->fillArray($array);
				break;
			default:
				throw new Exception\RuntimeException("Invalid metadata array: " . print_r($array, 1));
		}
		
		return $element;
	}
	
	/**
	 * Set properties from array
	 * @param array $array
	 */
	abstract protected function fillArray(array $array);
}
