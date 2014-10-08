<?php

namespace Supra\Package\Cms\Editable;

use Supra\Controller\Pages\Entity\ReferencedElement\ReferencedElementAbstract;

/**
 * Textarea with wysiwyg editor
 */
class HtmlTextarea extends Textarea
{
	const EDITOR_TYPE = 'Html';
	const EDITOR_INLINE_EDITABLE = false;
		
	/**
	 * @param string $content
	 */
	protected function getUnserializedContent()
	{
		$unserializedContent = array(
			'html' => null,
			'data' => null, 
			'fonts' => null,
		);
		
		if ( ! empty ($this->content)) {
			
			$unserializedValue = @unserialize($this->content);
			
			if (is_array($unserializedValue)) {
				$unserializedContent = $unserializedValue;
			} else {
				$unserializedContent['html'] = $this->content;
			}
		}
		
		return $unserializedContent;
	}
	
	public function getContentForEdit() 
	{
		return $this->getUnserializedContent();
	}
	
	public function setContentFromEdit($content) 
	{
		$this->content = serialize($content);
	}
	
	public function getFilteredValue() 
	{
		$content = $this->getUnserializedContent();
		
		$referencedElements = array();
		
		if ( ! empty($content['data'])) {
			foreach ($content['data'] as $name => $elementData) {
				$referencedElements[$name] = ReferencedElementAbstract::fromArray($elementData);
			}
		}
		
		$filter = new \Supra\Controller\Pages\Filter\ParsedHtmlFilter();
		$filter->setResponseContext(new \Supra\Response\ResponseContext);
		
		$filteredValue = $filter->doFilter($content['html'], $referencedElements);
		
		return $filteredValue;
	}
}
