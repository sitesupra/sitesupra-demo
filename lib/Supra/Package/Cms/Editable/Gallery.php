<?php

namespace Supra\Package\Cms\Editable;

/**
 * Gallery property
 */
class Gallery extends Editable
{
//	protected $dummyController;
	
	public function getEditorType()
	{
		return 'Gallery';
	}
//
//	public function isInlineEditable()
//	{
//		false;
//	}
//
//	public function getDummyBlockController()
//	{
//		if (is_null($this->dummyController)) {
//			$this->dummyController = new \Supra\Controller\Pages\GalleryBlockController;
//		}
//
//		return $this->dummyController;
//	}
}
