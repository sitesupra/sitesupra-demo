<?php

namespace Supra\Controller\Pages\Entity\ReferencedElement;

use Supra\Controller\Pages\Entity\Abstraction\Entity;
use Supra\ObjectRepository\ObjectRepository;

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
	 * Temporary function while migrating from serialized block property metadata
	 * @return array
	 */
	public function toArray()
	{
		return get_object_vars($this);
	}
	
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
				$element->setResource($array['resource']);
				$element->setTitle($array['title']);
				
				switch ($array['resource']) {
					case 'page':
						$element->setPage(ObjectRepository::getEntityManager($caller)
								->find('Supra\Controller\Pages\Entity\Page', $array['page_id']));
						break;
					case 'file':
						$element->setFile(ObjectRepository::getEntityManager($caller)
								->find('Supra\FileStorage\Entity\File', $array['file_id']));
						break;
					case 'link':
						$element->setHref($array['href']);
						break;
					default:
						throw new \Supra\Controller\Pages\Exception\RuntimeException("Invalid metadata array: " . print_r($array, 1));
				}
				break;
			case 'image':
				$element = new ImageReferencedElement();
				$element->setAlign($array['align']);
				$element->setStyle($array['style']);
				$element->setWidth($array['size_width']);
				$element->setHeight($array['size_height']);
				$element->setAlternativeText($array['title']);
				$element->setImage(ObjectRepository::getEntityManager($caller)
						->find('Supra\FileStorage\Entity\Image', $array['image']));
				break;
			default:
				throw new \Supra\Controller\Pages\Exception\RuntimeException("Invalid metadata array: " . print_r($array, 1));
		}
		
		return $element;
	}
}
