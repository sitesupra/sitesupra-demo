<?php

namespace Supra\Cms\BannerManager;

use Supra\Controller\SimpleController;

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
