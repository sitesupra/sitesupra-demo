<?php

namespace Supra\Package\Cms\Editable;

/**
 * File editable
 */
class File extends Editable
{
	const EDITOR_TYPE = 'File';
	
	/**
	 * Return editor type
	 * @return string
	 */
	public function getEditorType()
	{
		return static::EDITOR_TYPE;
	}
	
//	public function getContentForEdit()
//	{
//		$fileData = null;
//
//		if ( ! empty($this->content)) {
//			$fileStorage = \Supra\ObjectRepository\ObjectRepository::getFileStorage($this);
//			$file = $fileStorage->find($this->content, \Supra\FileStorage\Entity\File::CN());
//
//			if ($file !== null) {
//				$fileData = $fileStorage->getFileInfo($file);
//			}
//		}
//
//		return $fileData;
//	}
}
