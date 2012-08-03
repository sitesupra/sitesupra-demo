<?php

namespace Project\Blocks\Form;

use Supra\Form\FormBlockController;
use Symfony\Component\Form;
use Symfony\Component\Validator\Constraint;

class FormBlock extends FormBlockController
{

	public static function getPropertyDefinition()
	{
		$properties = self::createCustomErrorProperty(
				'Form field "Name" custom validation', 
				'name', 
				'Custom text "{{ custom }}" will be replaced',
				'custom_error_message'
		);
		
		return $properties;
	}

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

	protected function success()
	{
		$form = $this->getBindedForm();
		$data = $form->getClientData();

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
			$error = new Form\FormError('custom_error_message', array(
				'{{ custom }}' => 'blah blah blah',
			));
			
			$form->get('name')->addError($error);
		}

		parent::validate($event);
	}
}
