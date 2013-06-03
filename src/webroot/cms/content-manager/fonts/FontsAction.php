<?php

namespace Supra\Cms\ContentManager\Fonts;

/**
 */
class FontsAction extends \Supra\Cms\ContentManager\PageManagerAction
{
	/**
	 */
	public function listAction()
	{
		$fontList = $this->getGoogleCssFontList();
				
		$this->getResponse()
				->setResponseData($fontList);
	}
}
