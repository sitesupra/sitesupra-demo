<?php

namespace Project\Pages;

use Supra\Controller\Pages\BlockController;
use Supra\Editable;
use Supra\ObjectRepository\ObjectRepository;


class LoginBlock extends BlockController
{
	/**
	 * @return array
	 */
	public function getPropertyDefinition()
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
	public function execute()
	{
		$response = $this->getResponse();
		
		$session = ObjectRepository::getSessionManager($this)
				->getAuthenticationSpace();

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
