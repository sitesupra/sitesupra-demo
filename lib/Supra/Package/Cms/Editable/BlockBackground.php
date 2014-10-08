<?php

namespace Supra\Package\Cms\Editable;

use Supra\Controller\Pages\Entity\ReferencedElement\ImageReferencedElement;

/**
 * String editable content
 */
class BlockBackground extends EditableAbstraction {

	const EDITOR_TYPE = 'BlockBackground';
	const EDITOR_INLINE_EDITABLE = false;

	/**
	 * Return editor type
	 * @return string
	 */
	public function getEditorType()
	{
		return static::EDITOR_TYPE;
	}

	/**
	 * {@inheritdoc}
	 * @return boolean
	 */
	public function isInlineEditable()
	{
		return static::EDITOR_INLINE_EDITABLE;
	}
	
	/**
	 * 
	 * @param type $content
	 */
	public function setContent($content)
	{
		if (is_array($content) && isset($content['image']) && ! empty($content['image'])) {
			$this->contentMetadata = new ImageReferencedElement;
			$this->contentMetadata->fillArray($content['image']);
		} else {
			$this->contentMetadata = null;
		}
	}
	
	public function setContentFromEdit($content)
	{
		$this->setContent($content);
	}
	
	/**
	 *
	 */
	public function getContentForEdit()
	{
		$data = array(
			'classname' => null,
			'image' => null,
		);
		
		if ($this->contentMetadata instanceof ImageReferencedElement) {
			
			$imageId = $this->contentMetadata->getImageId();
			
			$data['image'] = $this->contentMetadata->toArray();
			
			$storage = \Supra\ObjectRepository\ObjectRepository::getFileStorage($this);
			$image = $storage->find($imageId, \Supra\FileStorage\Entity\Image::CN());
			
			if (is_null($image)) {
				\Log::warn("Failed to find image #{$imageId} for referenced element");
				return null;
			}
			
			$data['image']['image'] = $storage->getFileInfo($image);
			
			return $data;
		}
		
		return null;
	}
}
