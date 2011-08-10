<?php

namespace Supra\Cms\ContentManager;

use Supra\Controller\Pages\Entity;

/**
 * Controller containing common methods
 */
class PageManagerActionController extends CmsActionController
{
	protected function setRequestMethod()
	{
		
	}
	
	protected function getRequestParameter()
	{
		
	}
	
	/**
	 * @return Entity\Abstraction\Page
	 */
	protected function getPage()
	{
		$this->getRequest()
				->get();
	}
}
