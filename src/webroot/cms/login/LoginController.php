<?php

namespace Supra\Cms\Login;

use Supra\Controller\SimpleController;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\Log\Log;

/**
 * Media library controller
 */
class LoginController extends SimpleController
{
	/**
	 * Default action when no action is provided
	 * @var string
	 */
	protected static $defaultAction = 'index';
	
	public function indexAction()
	{
		
		//TODO: introduce some template engine
		$output = file_get_contents(__DIR__ . '/index.html');
		
		$this->getResponse()->output($output);
	}
}