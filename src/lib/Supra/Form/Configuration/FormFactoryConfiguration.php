<?php

namespace Supra\Form\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Symfony\Component\Validator;
use Supra\Form\FormClassMetadataCache;
use Symfony\Component\Form;
use Supra\Form\FormSupraExtension;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\Common\Annotations\AnnotationReader;

/**
 * Form factory configuration
 */
class FormFactoryConfiguration implements ConfigurationInterface
{
	public $caller = ObjectRepository::DEFAULT_KEY;

	public $extensions = array(
		
	);

	public function configure()
	{
		$annotationReader = new AnnotationReader();
		$annotationLoader = new Validator\Mapping\Loader\AnnotationLoader($annotationReader);

		$cache = new FormClassMetadataCache();
		$metadataFactory = new Validator\Mapping\ClassMetadataFactory($annotationLoader, $cache);
		$validatorFactory = new Validator\ConstraintValidatorFactory();

		$validator = new Validator\Validator($metadataFactory, $validatorFactory);

		$extensions = array(
			new Form\Extension\Core\CoreExtension(),
			new Form\Extension\Validator\ValidatorExtension($validator),
			new FormSupraExtension($metadataFactory),
			
		) + (array) $this->extensions;

		$formRegistry = new Form\FormRegistry($extensions);
		$formFactory = new Form\FormFactory($formRegistry);

		ObjectRepository::setFormFactory($this->caller, $formFactory, 'Symfony\Component\Form\FormFactoryInterface');
	}
}
