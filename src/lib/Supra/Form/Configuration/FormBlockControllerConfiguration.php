<?php

namespace Supra\Form\Configuration;

use ReflectionClass;
use Supra\Form\FormField;
use Symfony\Component\Validator\Constraint;
use Supra\Editable\String;
use Symfony\Component\Form\Form;
use Supra\Controller\Pages\Configuration\BlockControllerConfiguration;
use Supra\Controller\Pages\Configuration\BlockPropertyGroupConfiguration;
use Supra\Controller\Pages\Configuration\BlockPropertyConfiguration;

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
	 * @var Form
	 */
	public $form;

	private $originalMessages = array();

	public function configure()
	{
		// processing annotations
		$this->fields = $formFields = $this->processAnnotations();

		// configuring field groups: labels and errors
		if ( ! empty($formFields)) {
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

				$group->configure();

				if ($group instanceof BlockPropertyGroupConfiguration) {
					$this->propertyGroups[$group->id] = $group;
				}
			}
		}

		// processing fields
		foreach ($formFields as $field) {
			/* @var $field FormField */

//			$messages = array();
//
//			/**
//			 * mapping messages
//			 */
//			foreach ($field->getConstraints() as $constraint) {
//				/* @var $constraint Constraint */
//				foreach ($constraint->propertyMessages as $property => $originalMessage) {
//					$messages[$constraint->$property] = $originalMessage;
//				}
//			}

			$fieldErrorInfo = $field->getErrorinfo();

			/**
			 * adding labels to form block property list
			 */
			// splitting camelCase into words
			$labelParts = preg_split('/(?=[A-Z])/', $field->getName());
			$fieldLabel = ucfirst(mb_strtolower(join(' ', $labelParts)));

			$blockProperty = new BlockPropertyConfiguration();
			$editable = new String("Field \"{$fieldLabel}\" label");

			$editable->setDefaultValue($fieldLabel);

//			$editable->setGroupId(self::FORM_GROUP_ID_LABELS);

			$editableName = static::generateEditableName(self::FORM_GROUP_ID_LABELS, $field->getName());
			$this->properties[] = $blockProperty->fillFromEditable($editable, $editableName);

			/**
			 * adding errors to form block property list
			 */
			$i = 1;
			foreach ($fieldErrorInfo as $messageKey => $errorInfo) {

				$defaultMessage = $errorInfo['message'];
				$constraint = $errorInfo['constraint'];

				// Generate user friendly name for error input
				$constraintTitle = get_class($constraint);
				$constraintTitle = substr($constraintTitle, strrpos($constraintTitle, '\\') + 1);
				$constraintTitle = trim(implode(' ', preg_split('/(?=[A-Z])/', $constraintTitle)));

				$blockProperty = new BlockPropertyConfiguration();
				$editable = new String("Field \"$fieldLabel\" {$constraintTitle} error");
				$editable->setDefaultValue($defaultMessage);

//				$editable->setGroupId(self::FORM_GROUP_ID_LABELS);

				$editableName = static::generateEditableName(self::FORM_GROUP_ID_ERROR, $field->getName()) . '_' . $messageKey;
				$this->properties[] = $blockProperty->fillFromEditable($editable, $editableName);
				$i ++;
			}
		}

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
		if ( ! in_array($propertyGroup, array(self::FORM_GROUP_ID_ERROR, self::FORM_GROUP_ID_LABELS), true)) {
			throw new \RuntimeException("Not recognized property group ID $propertyGroup");
		}

		if ($field instanceof FormField) {
			return self::BLOCK_PROPERTY_FORM_PREFIX . $propertyGroup . '_' . $field->getName();
		} else {
			return self::BLOCK_PROPERTY_FORM_PREFIX . $propertyGroup . '_' . (string) $field;
		}
	}

	/**
	 * Reads Form Entity class and returns all annotation classes in array of FormField objects
	 * @return array of FormField objects
	 */
	public function processAnnotations()
	{
		// creating reflection
		$reader = new \Doctrine\Common\Annotations\AnnotationReader();
		$reflection = new ReflectionClass($this->form);

		$annotations = array();

		// gathering property annotations
		foreach ($reflection->getProperties() as $property) {
			/* @var $property ReflectionProperty */

			$name = $property->getName();
			$propertyAnnotations = $reader->getPropertyAnnotations($property);
			$errorInfo = array();
			$constraints = array();

			// gathering FormFields and unsetting not Constraint Annotations
			foreach ($propertyAnnotations as $annotation) {
				if ($annotation instanceof FormField) {
					$annotation->setName($name);
					$annotations[$name] = $annotation;
					continue;
				}

				if ($annotation instanceof Constraint) {
					
					$constraints[] = $annotation;
					// Now we have Constraint object
					$messageProperties = get_object_vars($annotation);

					foreach ($messageProperties as $messageKey => $messageValue) {

						$className = strtolower(array_pop(explode('\\', get_class($annotation))));

						if (stripos($messageKey, 'message') === strlen($messageKey) - 7) {
							$errorIdentifier = "constraint_{$className}_{$messageKey}";
							$errorInfo[$errorIdentifier] = array(
								'constraint' => $annotation,
								'message' => $messageValue,
							);
							$annotation->$messageKey = $errorIdentifier;
						}
					}
				}
			}

			// Not a field, skip
			if ( ! isset($annotations[$name])) {
				continue;
			}

			$formField = $annotations[$name];
			$formField->addConstraints($constraints);
			$formField->setErrorInfo($errorInfo);
		}

		return $annotations;
	}

}
