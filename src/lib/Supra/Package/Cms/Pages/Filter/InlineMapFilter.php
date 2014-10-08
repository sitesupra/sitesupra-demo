<?php

namespace Supra\Package\Cms\Pages\Filter;

/**
 *
 */
class InlineMapFilter implements \Supra\Editable\Filter\FilterInterface
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
		
		$mapData = array(
			'latitude' => null,
			'longitude' => null,
		);
		
		$value = $this->property->getValue();
		
		if ( ! empty($value) && ($pos = strpos($value, '|')) !== false) {
			$mapData['latitude'] = substr($value, 0, $pos);
			$mapData['longitude'] = substr($value, $pos+1);
		}
		
		return $mapData;
	}
	
}
