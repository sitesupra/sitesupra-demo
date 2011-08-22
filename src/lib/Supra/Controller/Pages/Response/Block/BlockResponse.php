<?php

namespace Supra\Controller\Pages\Response\Block;

use Supra\Response\HttpResponse;
use Supra\Editable\EditableInterface;
use Supra\Controller\Pages\Entity;

/**
 * Response for block
 */
abstract class BlockResponse extends HttpResponse
{
	/**
	 * @var Entity\Abstraction\Block
	 */
	private $block;
	
	/**
	 * @return Entity\Abstraction\Block
	 */
	public function getBlock()
	{
		return $this->block;
	}

	/**
	 * @param Block $block
	 */
	public function setBlock(Entity\Abstraction\Block $block)
	{
		$this->block = $block;
	}
	
	/**
	 * Get the content and output it to the response or return if requested
	 * 
	 * TODO: no editable mode for editables belonging to parent objects
	 * 
	 * @param Entity\BlockProperty $property
	 * @return string
	 */
	public function outputProperty(Entity\BlockProperty $property)
	{
		$valueData = $property->getValueData();
		$editable = $property->getEditable();
		$filteredValue = $editable->getFilteredValue(static::EDITABLE_FILTER_ACTION);
		
		if ($editable instanceof \Supra\Editable\Html) {
			
			//TODO: dummy replace for links only for now, must move to some filters
			$matches = array();
			preg_match_all('/\{supra\.link id="(.*?)"\}(.*?)(\{\/supra\.link\})/', $filteredValue, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
			
			$offset = 0;
			$final = '';
			
			foreach ($matches as $match) {
				
				$offsetInit = $match[0][1];
				$offsetEnd = $match[3][1] + strlen($match[3][0]);
				
				$id = $match[1][0];
				$content = $match[2][0];
				
				$data = $valueData[$id];
				
				//TODO: $data must be used to generate the links
				
				$final .= substr($filteredValue, $offset, $offsetInit - $offset);
				$final .= '<a href="#' . htmlspecialchars(serialize($data)) . '">' . $content . '</a>';
				
				$offset = $offsetEnd;
			}
			
			$final .= substr($filteredValue, $offset);
			
			$filteredValue = $final;
		}
		
		$this->output($filteredValue);
		
		return $filteredValue;
	}
}
