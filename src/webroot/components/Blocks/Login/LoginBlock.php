<?php

namespace Project\Blocks\Login;

use Supra\Controller\Pages\BlockController;
use Supra\Editable;
use Supra\ObjectRepository\ObjectRepository;


class LoginBlock extends BlockController
{
	/**
	 * Main method
	 */
	public function doExecute()
	{
		$response = $this->getResponse();
		
		// using the default namespace for now, because the prefilter does it as well
		$session = ObjectRepository::getSessionManager($this)
				->getDefaultSessionNamespace();

		if ( ! empty($session->login)) {
			$response->assign('login', $session->login);
			unset($session->login);
		}

		if ( ! empty($session->message)) {
			$response->assign('message', $session->message);
			unset($session->message);
		}
		
		
		$response->outputTemplate('login.html.twig');
	}
	
}
