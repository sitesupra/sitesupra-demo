<?php

namespace Project\Blocks\Form;

use Supra\Form\FormBlockController;
use Supra\Editable;
use Symfony\Component\Form;

class FormBlock extends FormBlockController
{

	protected function failure()
	{
		$response = $this->getResponse();
		/* @var $response \Supra\Response\TwigResponse */
		$response->outputTemplate('failure.html.twig');
	}

	protected function render()
	{
		$response = $this->getResponse();
		/* @var $response \Supra\Response\TwigResponse */

		$form = $this->getForm();
		$formView = $form->createView();

		$response->assign('form', $formView);
		$response->assign('hash', spl_object_hash($this));
		$response->outputTemplate('render.html.twig');
	}

	protected function success()
	{
		$form = $this->getForm();
		$data = $form->getClientData();

		$response = $this->getResponse();
		/* @var $response \Supra\Response\TwigResponse */
		$response->outputTemplate('success.html.twig');
	}

	protected function validate(array $data = array())
	{
		if($data['name'] == 'fail') {
			return false;
		}
		
		return true;
	}

}
