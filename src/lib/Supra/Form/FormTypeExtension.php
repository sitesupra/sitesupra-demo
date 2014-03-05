<?php

namespace Supra\Form;

use Symfony\Component\Validator;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * FormTypeExtension
 */
class FormTypeExtension extends AbstractTypeExtension
{
	/**
	 * @var Validator\Mapping\ClassMetadataFactory
	 */
	public $metadataFactory;

	function __construct(Validator\Mapping\ClassMetadataFactory $metadataFactory)
	{
		$this->metadataFactory = $metadataFactory;
	}

	public function buildForm(FormBuilderInterface $formBuilder, array $options)
	{
		$dataObject = $options['data'];

		$groups = (array) $formBuilder->getOption('validation_groups');
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
				}

				$formBuilder->add($propertyName, null, $options);
			}
		}

		$formBuilder->addEventSubscriber(new BindRequestListener());
	}

	public function getExtendedType()
	{
		return 'form';
	}

}
