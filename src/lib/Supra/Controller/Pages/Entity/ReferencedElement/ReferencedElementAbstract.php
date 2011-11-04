<?php

namespace Supra\Controller\Pages\Entity\ReferencedElement;

use Supra\Controller\Pages\Entity\Abstraction\Entity;
use Supra\Controller\Pages\Entity\Abstraction\AuditedEntity;
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
abstract class ReferencedElementAbstract extends Entity implements AuditedEntity
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
	public static function fromArray(array $array)
	{
		$element = null;
		
		switch ($array['type']) {
			
			case LinkReferencedElement::TYPE_ID:
				$element = new LinkReferencedElement();
				$element->fillArray($array);
				break;
			
			case ImageReferencedElement::TYPE_ID:
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
	abstract public function fillArray(array $array);
}
