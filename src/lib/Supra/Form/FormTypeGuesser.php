<?php

namespace Supra\Form;

use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Form\Guess\ValueGuess;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Mapping\ClassMetadataFactory;

/**
 * FormTypeGuesser
 */
class FormTypeGuesser implements FormTypeGuesserInterface
{
//	/**
//	 * @var FormAnnotationLoader
//	 */
//	private $annotationLoader;

	/**
	 * @var ClassMetadataFactory
	 */
	private $factory;

	public function __construct(ClassMetadataFactory $factory)
	{
		$this->factory = $factory;
	}

	public function guessMaxLength($class, $property)
	{
		return null;
	}

	public function guessMinLength($class, $property)
	{
		return null;
	}

	public function guessPattern($class, $property)
	{
		return null;
	}

	/**
	 * Will mark as not required if the annotation is under custom validation groups
	 * @param string $class
	 * @param string $property
	 * @return null|\Symfony\Component\Form\Guess\ValueGuess
	 */
	public function guessRequired($class, $property)
	{
		$metadata = $this->factory->getClassMetadata($class);
		/* @var $metadata \Symfony\Component\Validator\Mapping\ClassMetadata */

		if ( ! isset($metadata->properties[$property])) {
			return;
		}

		$propertyMetadata = $metadata->properties[$property];

		$matchFound = null;

		foreach ($propertyMetadata->getConstraints() as $propertyAnnotation) {

			if ($propertyAnnotation instanceof Constraints\NotBlank
					|| $propertyAnnotation instanceof Constraints\NotNull
					|| $propertyAnnotation instanceof Constraints\True
					) {
				if ( ! in_array(Constraint::DEFAULT_GROUP, $propertyAnnotation->groups)) {
					$matchFound = true;
				} else {
					$matchFound = false;
					break;
				}
			}
		}

		// Need to guess required=false if a constraint is found, but is under groups not in default validation group
		if ($matchFound === true) {
			return new ValueGuess(false, ValueGuess::HIGH_CONFIDENCE);
		}

		return null;
	}

	/**
	 * Guess type according to the FormField type parameter
	 * @param string $class
	 * @param string $property
	 * @return null|\Symfony\Component\Form\Guess\TypeGuess
	 */
	public function guessType($class, $property)
	{
		$metadata = $this->factory->getClassMetadata($class);
		/* @var $metadata \Symfony\Component\Validator\Mapping\ClassMetadata */
		$fields = $metadata->properties;

		if ( ! isset($fields[$property])) {
			return null;
		}

		$propertyMetadata = $fields[$property];
		/* @var	$propertyMetadata \Symfony\Component\Validator\Mapping\PropertyMetadata */

		foreach ($propertyMetadata->getConstraints() as $field) {

			if ($field instanceof FormField) {
				/* @var $field FormField */
				$type = $field->getType();

				if ( ! empty($type)) {
					return new TypeGuess($type, $field->getFieldOptions(), TypeGuess::HIGH_CONFIDENCE);
				}
			}
		}

		return null;
	}
}
