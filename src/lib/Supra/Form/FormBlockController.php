<?php

namespace Supra\Form;

use Supra\Controller\Pages\BlockController;
use Symfony\Component\Form;
use Symfony\Component\Validator;
use Supra\Loader\Loader;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Supra\ObjectRepository\ObjectRepository;

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

	/**
	 * @return string
	 */
	protected function getFormNamespace()
	{
		return $this->getBlock()->getId();
	}

	protected function doExecute()
	{
		$request = $this->getRequest();
		/* @var $request \Supra\Controller\Pages\Request\PageRequest */

		$this->bindedForm = $this->createForm();
		$name = $this->getFormNamespace();

		$conf = $this->getConfiguration();

		if (empty($conf->method) || strcasecmp($conf->method, 'get') == 0) {
			$input = $request->getQuery();
		} elseif (strcasecmp($conf->method, 'post') == 0) {
			$input = $request->getPost();
		} else {
			throw new \Supra\Configuration\Exception\InvalidConfiguration("Bad method '$conf->method' received in form configuration");
		}

		if ($input->hasChild($name)) {

			$this->bindedForm->bind($input->getChild($name)->getArrayCopy());
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
	 * @return Form\FormFactoryInterface
	 */
	protected function getFormFactory()
	{
		$factory = ObjectRepository::getFormFactory($this);

		return $factory;
	}

	/**
	 * @return Form\FormBuilder
	 * @throws Exception\RuntimeException
	 */
	protected function createFormBuilder()
	{
		$conf = $this->getConfiguration();
		$dataObject = Loader::getClassInstance($conf->dataClass);
		$dataObject = $this->initializeData($dataObject);
		
		$factory = $this->getFormFactory();

		$id = $this->getFormNamespace();
		$options = $this->getFormBuilderOptions();
		$formBuilder = $factory->createNamedBuilder($id, 'form', $dataObject, $options);

		// Custom events
		if ($this instanceof EventSubscriberInterface) {
			$formBuilder->addEventSubscriber($this);
		}

		// Custom validation
		$formBuilder->addEventListener(Form\FormEvents::POST_BIND, array($this, 'validate'), 0);

		return $formBuilder;
	}

	/**
	 * @return Form\Form
	 */
	protected function createForm()
	{
		$formBuilder = $this->createFormBuilder();

		return $formBuilder->getForm();
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
			'validation_groups' => $this->getFormValidationGroups(),
		);
	}

}
