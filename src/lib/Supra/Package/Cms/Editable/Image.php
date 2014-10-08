<?php

namespace Supra\Package\Cms\Editable;

/**
 * Image editable
 */
class Image extends EditableAbstraction
{
	const EDITOR_TYPE = 'Image';
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
	 * @return array | null
	 */
	public function getContentForEdit()
	{
		$fileData = null;
		
		if ( ! empty($this->content)) {
			$fileStorage = \Supra\ObjectRepository\ObjectRepository::getFileStorage($this);
			$file = $fileStorage->find($this->content, \Supra\FileStorage\Entity\Image::CN());
			
			if ($file !== null) {
				$fileData = $fileStorage->getFileInfo($file);
			}
		}
		
		return $fileData;
	}
}
