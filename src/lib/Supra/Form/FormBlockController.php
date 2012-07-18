<?php

namespace Supra\Form;

use Supra\Controller\Pages\BlockController;
use Symfony\Component\Form;
use Symfony\Component\HttpFoundation\Request;
use Supra\Form\Configuration\FormBlockControllerConfiguration;
use Symfony\Component\Validator\Mapping\Loader\AnnotationLoader;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Validator;
use Supra\Loader\Loader;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class FormBlockController extends BlockController
{
	/**
	 * @var Form\Form
	 */
	protected $bindedForm;

	/**
	 * @var Form\FormView
	 */
	protected $formView;

	protected function doExecute()
	{
		$request = $this->getRequest();
		/* @var $request \Supra\Controller\Pages\Request\PageRequest */

		$this->bindedForm = $this->createForm();
		$name = $this->getBlock()->getId();
		
		if ($request->getPost()->hasChild($name)) {

			// TODO: make it somehow better...
			$symfonyRequest = new Request(
					$request->getQuery()->getArrayCopy(),
					$request->getPost()->getArrayCopy(),
					array(),
					$request->getCookies(),
					$request->getPostFiles()->getArrayCopy(),
					$request->getServer());
			
			$this->bindedForm->bindRequest($symfonyRequest);
			
			$view = $this->getFormView();
			$this->getResponse()->assign('form', $view);

			if ($this->bindedForm->isValid()) {
				$this->success();
				return;
			} else {
				$this->failure();
				return;
			}
		} else {

			$view = $this->getFormView();
			$this->getResponse()->assign('form', $view);
			$this->render();
		}
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
		
	}

	/**
	 * @return Form\Form
	 */
	public function getBindedForm()
	{
		return $this->bindedForm;
	}

	public function getFormView()
	{
		if (is_null($this->formView)) {
			$this->formView = $this->bindedForm->createView();
		}

		return $this->formView;
	}

	/**
	 * @return Form\Form
	 */
	protected function createForm()
	{
		$conf = $this->getConfiguration();
		$dataObject = Loader::getClassInstance($conf->form);
		$formBuilder = $this->prepareFormBuilder($dataObject);
		
		foreach ($conf->fields as $field) {
			/* @var $field FormField */
			$options = $field->getArguments();

			$propertyGroup = FormBlockControllerConfiguration::FORM_GROUP_ID_LABELS;
			$propertyName = FormBlockControllerConfiguration::generateEditableName($propertyGroup, $field);
			$blockPropertyValue = $this->getPropertyValue($propertyName);

			if ( ! empty($blockPropertyValue)) {
				$options['label'] = $blockPropertyValue;
			}

			if ($field->getType() === FormField::TYPE_CHOICE) {
				
				$choiceList = $field->getArgument('choice_list');
				if ( ! is_null($choiceList)) {
					
					if ( ! class_exists($choiceList)) {
						throw new Exception\RuntimeException('Wrong class specified as choice list argument');
					}
					
					$choiceList = new $choiceList;
					$options['choice_list'] = $choiceList;
				}
			}
			
			$formBuilder->add($field->getName(), $field->getType(), $options);
		}

		// Custom events
		if ($this instanceof EventSubscriberInterface) {
			$formBuilder->addEventSubscriber($this);
		}

		// Old stuff...
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
	 * @param object $dataObject
	 * @return Form\FormBuilder 
	 */
	protected function prepareFormBuilder($dataObject)
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

		$formRegistry = new Form\FormRegistry(array(
					new Form\Extension\Core\CoreExtension(),
					new Form\Extension\Validator\ValidatorExtension($validator),
//					new Form\Extension\Csrf\CsrfExtension($csrfProvider)
				));

		$factory = new Form\FormFactory($formRegistry);

		$id = $this->getBlock()->getId();
		$formBuilder = $factory->createNamedBuilder($id, 'form', $dataObject);

		return $formBuilder;
	}

}