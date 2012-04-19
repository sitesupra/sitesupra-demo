<?php

namespace Project\Blocks\Login;

use Supra\Controller\Pages\BlockController;
use Supra\Editable;
use Supra\ObjectRepository\ObjectRepository;


class LoginBlock extends BlockController
{
	/**
	 * @return array
	 */
	public static function getPropertyDefinition()
	{
		$properties = array();
		
		$label = new Editable\String('Login label');
		$label->setDefaultValue('Login');
		$properties['loginLabel'] = $label;
		
		$label = new Editable\String('Password label');
		$label->setDefaultValue('Password');
		$properties['passwordLabel'] = $label;
		
		$label = new Editable\String('Login button label');
		$label->setDefaultValue('Login');
		$properties['loginButtonLabel'] = $label;
		
		return $properties;
	}
	
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
