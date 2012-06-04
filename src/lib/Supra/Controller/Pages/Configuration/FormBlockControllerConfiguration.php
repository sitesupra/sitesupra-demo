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
			// adding to form block property list
			$propertyTypes = array(self::FORM_GROUP_ID_ERROR => 'error message', self::FORM_GROUP_ID_LABELS => 'label');
			foreach ($propertyTypes as $propertyGroup => $fieldType) {
				$property = new BlockPropertyConfiguration();

				$editable = new \Supra\Editable\String("Form field \"{$field->label}\" ({$field->name}) {$fieldType}");

				//@TODO: Change when validation will be added
				if ($propertyGroup != self::FORM_GROUP_ID_ERROR) {
					$editable->setDefaultValue($field->label);
				}

//				$editable->setGroupId($propertyGroup);

				$editableName = static::generateEditableName($propertyGroup, $field);
				$this->properties[] = $property->fillFromEditable($editable, $editableName);
			}
		}

		parent::configure();
	}

	/**
	 * Generates editable name
	 * 
	 * @param string $propertyGroup
	 * @param FormFieldConfiguration $field
	 * @throws \RuntimeException if $propertyGroup is not on of FORM_GROUP_ID constants
	 * @return string 
	 */
	public static function generateEditableName($propertyGroup, FormFieldConfiguration $field)
	{
		if ( ! in_array($propertyGroup, array(self::FORM_GROUP_ID_ERROR, self::FORM_GROUP_ID_LABELS))) {
			throw new \RuntimeException('');
		}
		return self::BLOCK_PROPERTY_FORM_PREFIX . $propertyGroup . '_' . $field->name;
	}

}
