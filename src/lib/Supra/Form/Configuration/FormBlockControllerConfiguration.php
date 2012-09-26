<?php

namespace Supra\Form\Configuration;

use Supra\Form\FormField;
use Symfony\Component\Validator\Constraint;
use Supra\Editable\String;
use Supra\Controller\Pages\Configuration\BlockControllerConfiguration;
use Supra\Controller\Pages\Configuration\BlockPropertyGroupConfiguration;
use Supra\Controller\Pages\Configuration\BlockPropertyConfiguration;
use Supra\Form\FormAnnotationLoader;

class FormBlockControllerConfiguration extends BlockControllerConfiguration
{
	const FORM_GROUP_ID_ERROR = 'form_errors';
	const FORM_GROUP_LABEL_ERROR = 'Form error messages';
	const FORM_GROUP_ID_LABELS = 'form_labels';
	const FORM_GROUP_LABEL_LABELS = 'Form field lables';
	const BLOCK_PROPERTY_FORM_PREFIX = 'form_field_';

	/**
	 * @var string
	 */
	public $dataClass;

	/**
	 * @var string
	 */
	public $method = 'post';

	/**
	 * @var array
	 */
	private $fields;

	/**
	 * @var FormAnnotationLoader
	 */
	private $annotationLoader;

	/**
	 * @return array
	 */
	public function getFields()
	{
		return $this->fields;
	}

	/**
	 * @return FormAnnotationLoader
	 */
	public function getAnnotationLoader()
	{
		return $this->annotationLoader;
	}

	public function configure()
	{
		$this->annotationLoader = new FormAnnotationLoader();

		// processing annotations
		$this->fields = $formFields = $this->processAnnotations();

//		// configuring field groups: labels and errors
//		if ( ! empty($formFields)) {
//			// groups
//			$groups = array(
//				self::FORM_GROUP_ID_ERROR => self::FORM_GROUP_LABEL_ERROR,
//				self::FORM_GROUP_ID_LABELS => self::FORM_GROUP_LABEL_LABELS,
//			);
//
//			foreach ($groups as $key => $value) {
//				$group = new BlockPropertyGroupConfiguration();
//				$group->id = $key;
//				$group->type = 'sidebar';
//				$group->label = $value;
//
//				$group->configure();
//
//				if ($group instanceof BlockPropertyGroupConfiguration) {
//					$this->propertyGroups[$group->id] = $group;
//				}
//			}
//		}
//
//		// processing fields
//		foreach ($formFields as $field) {
//			/* @var $field FormField */
//
//			$fieldErrorInfo = $field->getErrorInfo();
//
//			/**
//			 * adding labels to form block property list
//			 */
//			// splitting camelCase into words
//			$labelParts = preg_split('/(?=[A-Z])/', $field->getName());
//
//			$fieldLabel = $field->getArgument('label');
//			if (empty($fieldLabel)) {
//				$fieldLabel = ucfirst(mb_strtolower(join(' ', $labelParts)));
//			}
//
//			$blockProperty = new BlockPropertyConfiguration();
//			$editable = new String("Field \"{$fieldLabel}\" label");
//
//			$editable->setDefaultValue($fieldLabel);
//
//			$editable->setGroupId(self::FORM_GROUP_ID_LABELS);
//
//			$editableName = static::generateEditableName(self::FORM_GROUP_ID_LABELS, $field->getName());
//			$this->properties[] = $blockProperty->fillFromEditable($editable, $editableName);
//
//			/**
//			 * adding errors to form block property list
//			 */
//			foreach ($fieldErrorInfo as $messageKey => $errorInfo) {
//
//				$defaultMessage = $errorInfo['message'];
//				$constraint = $errorInfo['constraint'];
//
//				// Humanize constraint name for error input
//				$className = get_class($constraint);
//				// .. assuming the classname contains backslash..
//				$classBasename = substr($className, strrpos($className, '\\') + 1);
//				$constraintTitle = trim(implode(' ', preg_split('/(?=[A-Z])/', $classBasename)));
//
//				$blockProperty = new BlockPropertyConfiguration();
//				$editable = new String("Field \"$fieldLabel\" {$constraintTitle} error");
//				$editable->setDefaultValue($defaultMessage);
//
//				$editable->setGroupId(self::FORM_GROUP_ID_LABELS);
//
//				$editableName = static::generateEditableName(self::FORM_GROUP_ID_ERROR, $field->getName()) . '_' . $messageKey;
//				$this->properties[] = $blockProperty->fillFromEditable($editable, $editableName);
//			}
//		}

		parent::configure();
	}

	/**
	 * Generates editable name
	 *
	 * @param string $propertyGroup
	 * @param FormField or string $field
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
	 * TODO: process method and class annotations as well
	 * @return array of FormField objects
	 */
	public function processAnnotations()
	{
		$classRefl = new \ReflectionClass($this->dataClass);
		$fields = array();

		while ( ! empty($classRefl)) {
			$propertyAnnotations = $this->annotationLoader->getPropertyAnnotations($classRefl->name);

			// gathering property annotations
			foreach ($propertyAnnotations as $name => $annotations) {

				// Don't overwrite already created fields
				if (isset($fields[$name])) {
					continue;
				}

//				$errorInfo = array();
				$formField = null;

				// gathering FormFields
				foreach ($annotations as $annotation) {
					if ($annotation instanceof FormField) {
						$annotation->setName($name);
						$formField = $annotation;
						break;
					}
				}

				// Not marked as field
				if (is_null($formField)) {
					continue;
				}

//				// gathering Constraint annotations
//				foreach ($annotations as $annotation) {
//					if ($annotation instanceof Constraint) {
//
//						// Now we have Constraint object
//						$messageProperties = get_object_vars($annotation);
//
//						foreach ($messageProperties as $messageKey => $messageValue) {
//
//							$className = strtolower(array_pop(explode('\\', get_class($annotation))));
//
//							if (stripos($messageKey, 'message') === strlen($messageKey) - 7) {
//
//								// For the block unique error identifier
//								$errorIdentifier = self::generateEditableName(self::FORM_GROUP_ID_ERROR, $formField)
//										. "_constraint_{$className}_{$messageKey}";
//
//								$annotation->$messageKey = $errorIdentifier;
//								$errorInfo[$errorIdentifier] = array(
//									'constraint' => $annotation,
//									'message' => $messageValue,
//								);
//							}
//						}
//					}
//				}

//				$formField->setErrorInfo($errorInfo);

				$fields[$name] = $formField;
			}

			$classRefl = $classRefl->getParentClass();
		}

		return $fields;
	}

}
