<?php

namespace Project\Blocks\Form;

use Supra\Form\FormBlockController;
use Symfony\Component\Form;
use Symfony\Component\Validator\Constraint;

class FormBlock extends FormBlockController
{

	protected function getFormValidationGroups()
	{
		if ( ! empty($_POST['developer_submit'])) {
			return array(Constraint::DEFAULT_GROUP, 'developer');
		} else {
			return array(Constraint::DEFAULT_GROUP);
		}
	}

	protected function failure()
	{
		$response = $this->getResponse();
		$response->outputTemplate('failure.html.twig');
	}

	protected function render()
	{
		$response = $this->getResponse();
		$response->outputTemplate('render.html.twig');
	}

	protected function success($data)
	{
		$response = $this->getResponse();
		/* @var $response \Supra\Response\TwigResponse */
		$response->outputTemplate('success.html.twig');
	}

	public function validate(Form\FormEvent $event)
	{
		$form = $event->getForm();
		$data = $event->getData();
		/* @var $data Entity\Form */
		
		if($data->getName() == 'customfail') {
			$error = new Form\FormError('Custom error');
			
			$form->get('name')->addError($error);
		}

		parent::validate($event);
	}
}
