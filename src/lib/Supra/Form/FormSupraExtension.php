<?php

namespace Supra\Form;

use Symfony\Component\Form\AbstractExtension;
use Symfony\Component\Validator;

/**
 * FormSupraExtension
 */
class FormSupraExtension extends AbstractExtension
{
	/**
	 * @var \Symfony\Component\Validator\Mapping\ClassMetadataFactory
	 */
	private $factory;

	public function __construct(Validator\Mapping\ClassMetadataFactory $factory)
	{
		$this->factory = $factory;
	}

	protected function loadTypeGuesser()
	{
		return new FormTypeGuesser($this->factory);
	}

	protected function loadTypes()
	{
		return array(
			new Type\FileIdType(),
			new Type\SubmitType(),
			new Type\DecimalType(),
		);
	}

	protected function loadTypeExtensions()
	{
		return array(new FormTypeExtension($this->factory));
	}
}
