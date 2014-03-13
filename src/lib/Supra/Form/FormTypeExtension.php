<?php

namespace Supra\Form;

use Symfony\Component\Validator;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;

/**
 * FormTypeExtension
 */
class FormTypeExtension extends AbstractTypeExtension
{
	/**
	 * @var Validator\Mapping\ClassMetadataFactory
	 */
	public $metadataFactory;

	public function __construct(Validator\Mapping\ClassMetadataFactory $metadataFactory)
	{
		$this->metadataFactory = $metadataFactory;
	}

	public function buildForm(FormBuilderInterface $formBuilder, array $options)
	{	
		if ( ! $formBuilder->getType()->getInnerType() instanceof FormType) {
			return null;
		}
		
		$dataObject = $options['data'];

		$validationGroups = $formBuilder->getOption('validation_groups');
		
		$groups = $this->resolveFormValidationGroups($validationGroups, $formBuilder);
		
		$metadata = $this->metadataFactory->getMetadataFor(get_class($dataObject));
		/* @var $metadata Validator\Mapping\ClassMetadata */
		$properties = $metadata->properties;

		foreach ($properties as $propertyName => $property) {
			/* @var $property Validator\Mapping\PropertyMetadata */

			$constraints = $property->getConstraints();

			foreach ($constraints as $field) {

				if ( ! $field instanceof FormField) {
					continue;
				}

				// Skip the field
				if ( ! $field->inGroups($groups)) {
					continue;
				}

				/* @var $field FormField */
				
				$fieldType = $field->getType();
				
				$options = $field->getFieldOptions();

				// Any better solution?
				if ($field->getType() === FormField::TYPE_CHOICE) {

					$choiceList = $field->getFieldOption('choice_list');
					if ( ! is_null($choiceList)) {

						if ( ! class_exists($choiceList)) {
							throw new Exception\RuntimeException('Wrong class specified as choice list argument');
						}

						$choiceList = new $choiceList;
						$options['choice_list'] = $choiceList;
					}
				} else if ($field->getType() === FormField::TYPE_REPEATED
						&& isset($options['repeated_type'])) {
						
					$options['type'] = $options['repeated_type'];
					unset($options['repeated_type']);
				} 

				$formBuilder->add($propertyName, $fieldType, $options);
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function getExtendedType()
	{
		return 'form';
	}

	/**
	 * @FIXME: $emptyForm always is w/o the data at this step
	 *		closure-type validation groups should be implemented in some another way
	 * 
	 * @param mixed $groups
	 * @return array
	 */
	private function resolveFormValidationGroups($groups, $formBuilder)
	{	
		if ( ! is_string($groups) && is_callable($groups)) {
			
			$emptyForm = $formBuilder->getForm();
			
            $groups = call_user_func($groups, $emptyForm);
        }

        return (array) $groups;
	}
}
