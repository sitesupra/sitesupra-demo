<?php

namespace Supra\Package\Cms\Pages\Filter;

use Supra\Editable\Filter\FilterInterface;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Controller\Pages\Entity\BlockPropertyMetadata;
use Supra\Controller\Pages\Entity\ReferencedElement\VideoReferencedElement;
use Supra\ObjectRepository\ObjectRepository;

/**
 *
 */
class VideoFilter implements FilterInterface
{
	/**
	 * @var BlockProperty
	 */
	public $property;
	
	/**
	 * Using filter to fetch usable metadata from link property
	 * @TODO: not nice
	 * @param string $content not used
	 * @return LinkReferencedElement
	 */
	public function filter($content)
	{
		$log = ObjectRepository::getLogger($this);
		
		if (empty($this->property)) {
			$log->warn("No property set");
			return null;
		}
		
		$metadata = $this->property->getMetadata()
				->get(0);
		
		if ( ! $metadata instanceof BlockPropertyMetadata) {
			$log->debug("No video metadata assigned");
			return null;
		}
		
		$element = $metadata->getReferencedElement();
		
		if ( ! $element instanceof VideoReferencedElement) {
			$log->warn("Referenced element invalid");
			return null;
		}
		
		// Filter embed code here!
	
		//@TODO: return object instead of array?
		return $element->toArray();
	}
}
