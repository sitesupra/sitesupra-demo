<?php

namespace Supra\Form;

use Supra\Controller\Pages\BlockController;
use Symfony\Component\Form;
use Symfony\Component\HttpFoundation\Request;
use Supra\Controller\Pages\Configuration\FormBlockControllerConfiguration;
use Symfony\Component\Validator\Mapping\Loader\AnnotationLoader;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Validator;

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

	/**
	 * Render form action
	 */
	abstract protected function render();

	/**
	 * On form success action
	 */
	abstract protected function success();

	/**
	 * On form failure action
	 */
	abstract protected function failure();

	/**
	 * Custom validation
	 * @return boolean 
	 */
	public function validate(Form\Event\DataEvent $event)
	{
		return true;
	}

	/**
	 * @return \Symfony\Component\Form\Form
	 */
	public function getBindedForm()
	{
		return $this->bindedForm;
	}

	/**
	 * @return \Symfony\Component\Form\Form
	 */
	protected function getCleanForm()
	{
		$conf = $this->getConfiguration();
		$formClass = new $conf->form;
		$formBuilder = $this->prepareFormBuilder($formClass);

		foreach ($conf->fields as $field) {
			/* @var $field FormField */
			$options = array();

			$propertyGroup = FormBlockControllerConfiguration::FORM_GROUP_ID_LABELS;
			$propertyName = FormBlockControllerConfiguration::generateEditableName($propertyGroup, $field);
			$blockPropertyValue = $this->getPropertyValue($propertyName);

			if ( ! empty($blockPropertyValue)) {
				$options['label'] = $blockPropertyValue;
			}

			$formBuilder->add($field->getName(), $field->getType(), $options);
		}

		// Custom validation
		$formBuilder->addEventListener(Form\FormEvents::POST_BIND, array($this, 'validate'), 10);

		return $formBuilder->getForm();
	}

	/**
	 *
	 * @param string $label Block property label
	 * @param string $formFieldName
	 * @param string $message Block property message.
	 * @param string $messageId error message id
	 * 
	 * @example 
	 * 
	 * self::createCustomErrorProperty(
	 * 				'Form field "Name" custom validation', 
	 * 				'name', 
	 * 				'Custom text "{{ custom }}" will be replaced',
	 * 			'custom_error_message'
	 * 	);
	 * 
	 * So that will be handled properly with 
	 * 
	 * $error = new Form\FormError('custom_error_message', array(
	 * 		'{{ custom }}' => 'blah blah blah',
	 * 	));
	 * 
	 * $form->get('name')->addError($error);
	 * 
	 * @return array 
	 */
	protected static function createCustomErrorProperty($label, $formFieldName, $message, $messageId)
	{
		$propertyName = FormBlockControllerConfiguration::generateEditableName(
						FormBlockControllerConfiguration::FORM_GROUP_ID_ERROR, $formFieldName)
				. "_{$messageId}";

		$error = new \Supra\Editable\String($label);
		$error->setDefaultValue($message);

		return array($propertyName => $error);
	}

	/**
	 * @return Form\FormBuilder 
	 */
	protected function prepareFormBuilder($class)
	{
//		@TODO: Add CSRF later
//		$csrfProvider = new Form\Extension\Csrf\CsrfProvider\DefaultCsrfProvider(uniqid());

		$path = SUPRA_LIBRARY_PATH . 'Symfony' . DIRECTORY_SEPARATOR . 'Component'
				. DIRECTORY_SEPARATOR . 'Form' . DIRECTORY_SEPARATOR . 'Resources'
				. DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'validation.xml';

		$loaderChain = new Validator\Mapping\Loader\LoaderChain(array(
					new AnnotationLoader(new AnnotationReader()),
					new \Symfony\Component\Validator\Mapping\Loader\XmlFileLoader($path),
				));

		$metadataFactory = new Validator\Mapping\ClassMetadataFactory($loaderChain);
		$validatorFactory = new Validator\ConstraintValidatorFactory();

		$validator = new Validator\Validator($metadataFactory, $validatorFactory);

		$factory = new Form\FormFactory(array(
					new Form\Extension\Core\CoreExtension(),
					new Form\Extension\Validator\ValidatorExtension($validator),
//					new Form\Extension\Csrf\CsrfExtension($csrfProvider)
				));

		$id = $this->getBlock()->getId();
		$formBuilder = $factory->createNamedBuilder('form', $id, $class);

		return $formBuilder;
	}

}