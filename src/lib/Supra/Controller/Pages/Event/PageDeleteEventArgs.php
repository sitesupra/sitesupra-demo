<?php

namespace Supra\Controller\Pages\Event;

use Doctrine\Common\EventArgs;
use Supra\User\Entity\User;
use Supra\Controller\Pages\Entity\Abstraction\Localization;

class PageDeleteEventArgs extends EventArgs
{
	/**
	 * @var string
	 */
	protected $page;
	
	public function getPageId()
	{
		return $this->page;
	}
	
	public function setPageId($pageId)
	{
		$this->page = $pageId;
	}
}
