<?php

namespace Supra\Cms\BannerManager;

use Supra\Controller\SimpleController;
use Supra\Authorization\AuthorizedControllerInterface;
use Supra\User\Entity\Abstraction\User;

/**
 * Banner Manager controller
 */
class BannerManagerController extends SimpleController
{
	/**
	 * Main action
	 */
	public function indexAction()
	{
		$output = file_get_contents(__DIR__ . '/index.html');
		
		$this->getResponse()
				->output($output);
	}	
}
