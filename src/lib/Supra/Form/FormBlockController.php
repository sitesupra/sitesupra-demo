<?php

namespace Supra\Form;

use Supra\Controller\Pages\BlockController;
use Symfony\Component\Form;
use Symfony\Component\HttpFoundation\Request;
use Supra\Controller\Pages\Configuration\FormBlockControllerConfiguration;

abstract class FormBlockController extends BlockController
{

	/**
	 * @var Form\Form
	 */
	protected $bindedForm;

	protected function doExecute()
	{
		$request = new Request($_GET, $_POST, array(), $_COOKIE, $_FILES, $_SERVER);

		$form = $this->getCleanForm();
		
		

		if ($request->isMethod('POST')) {

			$form->bindRequest($request);

			$this->bindedForm = $form;

			if ($form->isValid()) {
				$this->success();
				return;
			} else {
				$this->failure();
				return;
			}
		}

		$this->render();
	}

	abstract protected function render();

	abstract protected function success();

	abstract protected function failure();

	/**
	 * Custom validation
	 * @return boolean 
	 */
	public function validate(Form\Event\DataEvent $event)
	{
		return true;
	}

	public function getBindedForm()
	{
		return $this->bindedForm;
	}

	/**
	 * @return \Symfony\Component\Form\Form
	 */
	protected function getCleanForm()
	{
		$formBuilder = $this->prepareFormBuilder();
		$conf = $this->getConfiguration();

		foreach ($conf->fields as $field) {
			/* @var $field FormFieldConfiguration */
			$options = array();

			if ($field->label) {
				$propertyGroup = FormBlockControllerConfiguration::FORM_GROUP_ID_LABELS;
				$propertyName = FormBlockControllerConfiguration::generateEditableName($propertyGroup, $field);
				$blockPropertyValue = $this->getPropertyValue($propertyName);

				if ( ! empty($blockPropertyValue)) {
					$options['label'] = $blockPropertyValue;
				} else {
					$options['label'] = $field->label;
				}
			}

			$constraints = array();

			foreach ($field->validation as $validation) {
				/* @var $validation \Supra\Controller\Pages\Configuration\FormFieldValidationConfiguration */
				$constraints[] = $validation->constraint;
			}

			/**
			 * The option "validation_constraint" was deprecated in 2.1 and will be removed in Symfony 2.3. 
			 * You should use the option "constraints" instead, where you can pass one or more constraints for a form.
			 * 
			 * $builder->add('name', 'text', array(
			 *    'constraints' => array(
			 *        new NotBlank(),
			 *        new MinLength(3),
			 *    ),
			 * ));
			 * 
			 * @FIXME
			 * @TODO
			 * 
			 * Currently using 2.0 version
			 * 
			 * @see https://github.com/symfony/symfony/blob/master/UPGRADE-2.1.md
			 */
			if ( ! empty($constraints)) {
				$collectionConstraint = new \Symfony\Component\Validator\Constraints\Collection($constraints);
				$options['validation_constraint'] = $collectionConstraint;
			}

			$formBuilder->add($field->name, $field->type, $options);
		}

		$formBuilder->addEventListener(Form\FormEvents::POST_BIND, array($this, 'validate'), 10);
//		$validator = new Form\Extension\Core\EventListener\ValidationListener();
		return $formBuilder->getForm();
	}

	/**
	 * @return \Symfony\Component\Form\FormBuilder 
	 */
	protected function prepareFormBuilder()
	{
		$csrfProvider = new Form\Extension\Csrf\CsrfProvider\DefaultCsrfProvider(uniqid());

		$reader = new \Doctrine\Common\Annotations\AnnotationReader();
		$loader = new \Symfony\Component\Validator\Mapping\Loader\AnnotationLoader($reader);
		$metadataFactory = new \Symfony\Component\Validator\Mapping\ClassMetadataFactory($loader);

		$validatorFactory = new \Symfony\Component\Validator\ConstraintValidatorFactory();
		$validator = new \Symfony\Component\Validator\Validator($metadataFactory, $validatorFactory);
		
		$factory = new Form\FormFactory(array(
					new Form\Extension\Validator\ValidatorExtension($validator),
					new Form\Extension\Core\CoreExtension(),
					new Form\Extension\Csrf\CsrfExtension($csrfProvider)
				));

		$dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();

		$id = $this->getBlock()->getId();
		$formBuilder = new \Symfony\Component\Form\FormBuilder($id, $factory, $dispatcher);
		
//		$validatorListener = new Form\Extension\Validator\EventListener\DelegatingValidationListener($validator);
//		$formBuilder->addEventSubscriber($validatorListener);
		
		return $formBuilder;
	}

}