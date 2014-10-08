<?php

namespace Supra\Package\Cms\Pages\Filter;

/**
 *
 */
class SlideshowFilter implements \Supra\Editable\Filter\FilterInterface
{
	/**
	 * @var \Supra\Controller\Pages\Entity\BlockProperty
	 */
	public $property;
	
	/**
	 *
	 */
	public function filter($content)
	{
		if (empty($this->property)) {
			\Log::warn("No property set");
			return null;
		}
		
		$value = $this->property->getValue();
		$valueArray = unserialize($value);
		
		return $valueArray;
	}
	
}
