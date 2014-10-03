<?php

namespace Supra\Package\Cms\Entity\ReferencedElement;

use Supra\Package\Cms\Entity\Abstraction\Entity;
use Supra\Package\Cms\Entity\Abstraction\VersionedEntity;
use Supra\Package\Cms\Entity\Abstraction\AuditedEntity;

use Supra\Controller\Pages\Exception;

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *		"link" = "LinkReferencedElement", 
 *		"image" = "ImageReferencedElement",
 *		"video" = "VideoReferencedElement",
 *		"icon" = "IconReferencedElement",
 * })
 */
abstract class ReferencedElementAbstract extends VersionedEntity implements
	AuditedEntity
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
				break;
			
			case ImageReferencedElement::TYPE_ID:
				$element = new ImageReferencedElement();
				break;
			
			case VideoReferencedElement::TYPE_ID:
				$element = new VideoReferencedElement();
				break;
			
			case IconReferencedElement::TYPE_ID:
				$element = new IconReferencedElement();
				break;
			
			default:
				throw new Exception\RuntimeException("Invalid metadata array: " . print_r($array, 1));
		}
		
		$element->fillArray($array);
		
		return $element;
	}
	
	/**
	 * Set properties from array
	 * @param array $array
	 */
	abstract public function fillArray(array $array);
}
