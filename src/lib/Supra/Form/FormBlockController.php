<?php

namespace Supra\Form;

use Supra\Controller\Pages\BlockController;
use Symfony\Component\Form;
use Symfony\Component\HttpFoundation\Request;
use Supra\Form\Configuration\FormBlockControllerConfiguration;
use Symfony\Component\Validator;
use Supra\Loader\Loader;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @method \Supra\Form\Configuration\FormBlockControllerConfiguration getConfiguration()
 */
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

		$conf = $this->getConfiguration();

		if (empty($conf->method) || strcasecmp($conf->method, 'get') == 0) {
			$input = $request->getQuery();
		} elseif (strcasecmp($conf->method, 'post') == 0) {
			$input = $request->getPost();
		} else {
			throw new \Supra\Configuration\Exception\InvalidConfiguration("Bad method '$conf->method' received in form configuration");
		}

		if ($input->hasChild($name)) {

			// TODO: make it somehow better...
			$symfonyRequest = new Request(
					$request->getQuery()->getArrayCopy(),
					$request->getPost()->getArrayCopy(),
					array(),
					$request->getCookies(),
					$request->getPostFiles()->getArrayCopy(),
					$request->getServer());
			
			$this->bindedForm->bind($symfonyRequest);
			$view = $this->getFormView();
			
			if ($this->bindedForm->isValid()) {
				$data = $this->bindedForm->getData();
				$this->success($data);
			} else {
				$this->failure();
			}
		} else {
			$view = $this->getFormView();
			$this->render();
		}
	}

	/**
	 * Render form action
	 */
	abstract protected function render();

	/**
	 * On form success action
	 * @param mixed $data
	 */
	abstract protected function success($data);

	/**
	 * On form failure action
	 */
	abstract protected function failure();

	/**
	 * Custom validation
	 * @param \Symfony\Component\Form\FormEvent $event
	 */
	public function validate(Form\FormEvent $event)
	{
		
	}

	/**
	 * @param \Symfony\Component\Form\FormEvent $event
	 */
	public function errorMessageTranslationListener(Form\FormEvent $event)
	{
		$form = $event->getForm();
		$this->translateErrorMessages($form);
	}

	/**
	 * @param \Symfony\Component\Form\FormInterface $form
	 */
	private function translateErrorMessages(Form\FormInterface $form)
	{
		$errors = $form->getErrors();

		foreach ($errors as $error) {
			/* @var $error Form\FormError */
			$message = $error->getMessageTemplate();
			$messageLocalized = null;
			
			$propertyName = FormBlockControllerConfiguration::generateEditableName(FormBlockControllerConfiguration::FORM_GROUP_ID_ERROR, $form->getName())
					. '_' . $message;

			if ($this->hasProperty($propertyName)) {
				$messageLocalized = $this->getPropertyValue($propertyName);
			} else {
				$this->log->warn("Error message '$message' not localized");
				$messageLocalized = $message;
			}

			$error->__construct($messageLocalized, $error->getMessageParameters(), $error->getMessagePluralization());
		}

		foreach ($form->all() as $element) {
			$this->translateErrorMessages($element);
		}
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
			$this->createFormView();
		}

		return $this->formView;
	}

	protected function createFormView()
	{
		$this->formView = $this->bindedForm->createView();
		$this->getResponse()->assign('form', $this->formView);
	}

	/**
	 * Data object can be filled with initial values in this stage
	 * @param mixed $data
	 * @return mixed
	 */
	protected function initializeData($data)
	{
		return $data;
	}

	/**
	 * @return Form\Form
	 */
	protected function createForm()
	{
		$conf = $this->getConfiguration();
		$dataObject = Loader::getClassInstance($conf->dataClass);
		$dataObject = $this->initializeData($dataObject);
		$formBuilder = $this->prepareFormBuilder($dataObject);
		$groups = (array) $formBuilder->getOption('validation_groups');
		
		foreach ($conf->getFields() as $field) {
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

			// Skip the field
			if ( ! $field->inGroups($groups)) {
				continue;
			}
			
			$formBuilder->add($field->getName(), null, $options);
		}

		// Custom events
		if ($this instanceof EventSubscriberInterface) {
			$formBuilder->addEventSubscriber($this);
		}

		// Custom validation
		$formBuilder->addEventListener(Form\FormEvents::POST_BIND, array($this, 'validate'), 0);

		// Error message translation using block properties
		$formBuilder->addEventListener(Form\FormEvents::POST_BIND, array($this, 'errorMessageTranslationListener'), 0);

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
	 * Validation groups can be provided
	 * @return array
	 */
	protected function getFormValidationGroups()
	{
		return array(Validator\Constraint::DEFAULT_GROUP);
	}

	/**
	 * Form builder options can be overriden
	 * @return array
	 */
	protected function getFormBuilderOptions()
	{
		return array(
			'validation_groups' => $this->getFormValidationGroups()
		);
	}

	/**
	 * Possiblity to add additional extensions
	 * @return array
	 */
	protected function getFormExtensions()
	{
		return array();
	}

	/**
	 * @param object $dataObject
	 * @return Form\FormBuilder 
	 */
	protected function prepareFormBuilder($dataObject)
	{
//		@TODO: Add CSRF later
//		$csrfProvider = new Form\Extension\Csrf\CsrfProvider\DefaultCsrfProvider(uniqid());

		$configuration = $this->getConfiguration();
		$annotationLoader = $configuration->getAnnotationLoader();

		$cache = new FormClassMetadataCache();
		$metadataFactory = new Validator\Mapping\ClassMetadataFactory($annotationLoader, $cache);
		$validatorFactory = new Validator\ConstraintValidatorFactory();

		$validator = new Validator\Validator($metadataFactory, $validatorFactory);

		$extensions = array(
			new Form\Extension\Core\CoreExtension(),
			new Form\Extension\Validator\ValidatorExtension($validator),
			new FormSupraExtension($configuration),
//			new Form\Extension\Csrf\CsrfExtension($csrfProvider)
		);

		$extensions = array_merge($extensions, $this->getFormExtensions());

		$formRegistry = new Form\FormRegistry($extensions);

		$factory = new Form\FormFactory($formRegistry);

		$id = $this->getBlock()->getId();
		$options = $this->getFormBuilderOptions();
		$formBuilder = $factory->createNamedBuilder($id, 'form', $dataObject, $options);

		

		return $formBuilder;
	}

}
