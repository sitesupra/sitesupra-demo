<?php

namespace Supra\Form;

use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess\TypeGuess;
use Supra\Form\Configuration\FormBlockControllerConfiguration;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Form\Guess\ValueGuess;
use Symfony\Component\Validator\Constraints;

/**
 * FormTypeGuesser
 */
class FormTypeGuesser implements FormTypeGuesserInterface
{
	/**
	 * @var FormBlockControllerConfiguration
	 */
	private $blockConfiguration;

	public function __construct(FormBlockControllerConfiguration $blockConfiguration)
	{
		$this->blockConfiguration = $blockConfiguration;
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
		$blockConfiguration = $this->blockConfiguration;
		$classPropertyAnnotations = $blockConfiguration->getAnnotationLoader()
				->getPropertyAnnotations($class);

		if ( ! isset($classPropertyAnnotations[$property])) {
			return null;
		}

		$propertyAnnotations = $classPropertyAnnotations[$property];

		$matchFound = null;

		foreach ($propertyAnnotations as $propertyAnnotation) {

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
		$blockConfiguration = $this->blockConfiguration;

		if ( ! $blockConfiguration instanceof FormBlockControllerConfiguration) {
			return null;
		}

		/* @var $blockConfiguration FormBlockControllerConfiguration */
		$fields = $blockConfiguration->getFields();

		if (isset($fields[$property])) {

			$field = $fields[$property];
			/* @var $field FormField */
			$type = $field->getType();

			if ( ! empty($type)) {
				return new TypeGuess($type, $field->getArguments(), TypeGuess::HIGH_CONFIDENCE);
			}
		}

		return null;
	}
}
