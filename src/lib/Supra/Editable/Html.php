<?php

namespace Supra\Editable;

/**
 * Html editable content, extends string so the string could be extended to HTML content
 */
class Html extends String
{
	const EDITOR_TYPE = 'InlineHTML';
	const EDITOR_INLINE_EDITABLE = true;
	
	
	/**
	 * @param mixed $content
	 */
	public function setContentFromEdit($content)
	{
		if (is_array($content)) {
			if ( ! isset($content['html'])) {
				throw new Exception\RuntimeException("Incorrect array content {$content} received for Html editable");
			}
			
			$this->content = $content['html'];
			$this->contentMetadata = isset($content['data']) ? $content['data'] : array();
			
		} else {
			$this->content = $content;
			$this->contentMetadata = array();
		}
	}
	
	/**
	 * @return array
	 */
	public function getContentForEdit()
	{
		$metaArray = array();
		
		foreach ($this->contentMetadata as $key => $metaItem) {
			
			if ($metaItem instanceof \Supra\Controller\Pages\Entity\BlockPropertyMetadata) {
			
				$element = $metaItem->getReferencedElement();
				$metaArray[$key] = $this->convertReferencedElementToArray($element);
			} else {
				$metaArray[$key] = $metaItem;
			}
		}
		
		return array(
			'html' => $this->content,
			'data' => $metaArray,
		);
	}
	
}
