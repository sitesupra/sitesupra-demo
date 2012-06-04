<?php

namespace Supra\Controller\Pages\Configuration;

use Supra\Controller\Pages\BlockControllerCollection;
use Supra\Loader\Loader;
use Supra\Configuration\ConfigurationInterface;
use Supra\Configuration\ComponentConfiguration;
use Symfony\Component\Form;
use \ReflectionClass;

class FormBlockControllerConfiguration extends BlockControllerConfiguration
{
	const FORM_GROUP_ID_ERROR = 'form_errors';
	const FORM_GROUP_LABEL_ERROR = 'Form error messages';

	const FORM_GROUP_ID_LABELS = 'form_labels';
	const FORM_GROUP_LABEL_LABELS = 'Form field lables';

	const BLOCK_PROPERTY_FORM_PREFIX = 'form_field_';

	/**
	 * @var array 
	 */
	public $constraints;

	/**
	 * @var array 
	 */
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

		$constraints = array();

		foreach ($this->fields as $field) {
			/* @var $field FormFieldConfiguration */

			$messages = array();

			foreach ($field->validation as $validation) {
				/* @var $validation \Supra\Controller\Pages\Configuration\FormFieldValidationConfiguration */

				$constraint = $validation->constraint;
				$reflection = new ReflectionClass($constraint);
				$properties = $reflection->getDefaultProperties();

				foreach ($properties as $key => $value) {
					if (strpos($key, 'message') !== false) {
						$className = strtolower(array_pop(explode('\\', $reflection->getName())));

						$propertyName = "constraint_{$className}_{$key}";
						$messages[$propertyName] = $value;

						$constraint->$key = $propertyName;
					}
				}

				$constraints[$field->name][] = $constraint;
			}

			// adding labels to form block property list
			$property = new BlockPropertyConfiguration();
			$editable = new \Supra\Editable\String("Field \"{$field->name}\" label");

			$editable->setDefaultValue($field->label);
			$editable->setGroupId(self::FORM_GROUP_ID_LABELS);

			$editableName = static::generateEditableName(self::FORM_GROUP_ID_LABELS, $field);
			$this->properties[] = $property->fillFromEditable($editable, $editableName);

			// adding errors to form block property list
			$i = 1;
			foreach ($messages as $key => $value) {
				$property = new BlockPropertyConfiguration();
				$editable = new \Supra\Editable\String("Field \"{$field->name}\" error #{$i}");
				$editable->setDefaultValue($value);

				$editable->setGroupId(self::FORM_GROUP_ID_LABELS);

				$editableName = static::generateEditableName(self::FORM_GROUP_ID_ERROR, $field) . '_' . $key;
				$this->properties[] = $property->fillFromEditable($editable, $editableName);
				$i++;
			}
		}

		$this->constraints = $constraints;

		parent::configure();
	}

	/**
	 * Generates editable name
	 * 
	 * @param string $propertyGroup
	 * @param FormFieldConfiguration or string $field 
	 * @throws \RuntimeException if $propertyGroup is not on of FORM_GROUP_ID constants
	 * @return string 
	 */
	public static function generateEditableName($propertyGroup, $field)
	{
		if ( ! in_array($propertyGroup, array(self::FORM_GROUP_ID_ERROR, self::FORM_GROUP_ID_LABELS))) {
			throw new \RuntimeException('');
		}

		if ($field instanceof FormFieldConfiguration) {
			return self::BLOCK_PROPERTY_FORM_PREFIX . $propertyGroup . '_' . $field->name;
		} else {
			return self::BLOCK_PROPERTY_FORM_PREFIX . $propertyGroup . '_' . (string) $field;
		}
	}

}
