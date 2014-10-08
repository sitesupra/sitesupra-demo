<?php

namespace Supra\Package\Cms\Pages\Filter;

use Supra\Editable\Filter\FilterInterface;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Controller\Pages\Entity\BlockPropertyMetadata;
use Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Converts filter metadata into usable link information array
 */
class LinkFilter implements FilterInterface
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
		
		$linkMetadata = $this->property->getMetadata()
				->get(0);
		
		if ( ! $linkMetadata instanceof BlockPropertyMetadata) {
			$log->debug("No link metadata assigned");
			return null;
		}
		
		$linkReferencedElement = $linkMetadata->getReferencedElement();
		
		if ( ! $linkReferencedElement instanceof LinkReferencedElement) {
			$log->warn("Referenced element invalid");
			return null;
		}
		
		return $linkReferencedElement;
	}
}
