<?php

namespace Supra\Cms\InternalUserManager\Root;

use Supra\Controller\SimpleController;

/**
 */
class RootAction extends SimpleController
{
	public function indexAction()
	{
		

		//TODO: introduce some template engine
		$output = file_get_contents(dirname(__DIR__) . '/index.html');
		
		$this->getResponse()->output($output);
	}
}