<?php

namespace Supra\Controller\Pages\Configuration;

use Supra\Controller\Pages\BlockControllerCollection;
use Supra\Loader\Loader;
use Supra\Configuration\ConfigurationInterface;
use Supra\Configuration\ComponentConfiguration;
use Symfony\Component\Form;

class FormBlockControllerConfiguration extends BlockControllerConfiguration
{
	const FORM_GROUP_ID_ERROR = 'form_errors';
	const FORM_GROUP_LABEL_ERROR = 'Form error messages';

	const FORM_GROUP_ID_LABELS = 'form_labels';
	const FORM_GROUP_LABEL_LABELS = 'Form field lables';

	const BLOCK_PROPERTY_FORM_PREFIX = 'form_field_';

	public $fields;

	/**
	 * @var \Symfony\Component\Form\Form
	 */
	public $form;

	public function configure()
	{
		$formBuilder = $this->getFormBuilder($this->class);


		if ( ! empty($this->fields)) {
			// groups 
			$groups = array(
				self::FORM_GROUP_ID_ERROR => self::FORM_GROUP_LABEL_ERROR,
				self::FORM_GROUP_ID_LABELS => self::FORM_GROUP_LABEL_LABELS,
			);

			foreach ($groups as $key => $value) {
				$group = new BlockPropertyGroupConfiguration();
				$group->id = $key;
				$group->type = 'sidebar';
				$group->label = $value;

				$group = $group->configure();

				if ($group instanceof BlockPropertyGroupConfiguration) {
					$this->propertyGroups[$group->id] = $group;
				}
			}
		}

		foreach ($this->fields as $field) {
			/* @var $field FormFieldConfiguration */
			// adding to form builder
			$formBuilder->add($field->name, $field->type);
			$formBuilder->addValidator($validator);
			
			new \Symfony\Component\Validator\Validator();

			// adding to form block property list
			$propertyTypes = array(self::FORM_GROUP_ID_ERROR => 'error message', self::FORM_GROUP_ID_LABELS => 'label');
			foreach ($propertyTypes as $propertyGroup => $fieldType) {
				$property = new BlockPropertyConfiguration();

				$editable = new \Supra\Editable\String("Form field \"{$field->label}\" ({$field->name}) {$fieldType}");
				
				//@TODO: Change when validation will be added
				if ($propertyGroup != self::FORM_GROUP_ID_ERROR) {
					$editable->setDefaultValue($field->label);
				}
				
				$editable->setGroupId($propertyGroup);

				$editableName = self::BLOCK_PROPERTY_FORM_PREFIX . $propertyGroup . '_' . $field->name;
				$this->properties[] = $property->fillFromEditable($editable, $editableName);
			}
		}

		$this->form = $formBuilder->getForm();

		parent::configure();
	}

	/**
	 * Temporary solution
	 * @TODO
	 * @return \Symfony\Component\Form\FormBuilder 
	 */
	protected function getFormBuilder($id)
	{
		$csrfProvider = new Form\Extension\Csrf\CsrfProvider\DefaultCsrfProvider(uniqid());

		$factory = new Form\FormFactory(array(
					new Form\Extension\Core\CoreExtension(),
					new Form\Extension\Csrf\CsrfExtension($csrfProvider)
				));

		$dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();

		$id = $this->prepareClassId($id);
		$formBuilder = new \Symfony\Component\Form\FormBuilder($id, $factory, $dispatcher);

		return $formBuilder;
	}

}
