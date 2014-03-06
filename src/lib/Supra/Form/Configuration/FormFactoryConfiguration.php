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

		$translator = new \Symfony\Component\Translation\IdentityTranslator(
				new \Symfony\Component\Translation\MessageSelector());
		
		
		$validator = new Validator\Validator($metadataFactory, $validatorFactory, $translator);

		$managerRegistry = new \Supra\Database\Doctrine\ManagerRegistry(
				null, array(null), array('#public'), //array('#public', '#draft', '#audit'),
				null, '#public', 'Doctrine\ORM\Proxy\Proxy'			
		);
		
		$extensions = array_merge(array(
			new Form\Extension\Core\CoreExtension(),
			new Form\Extension\Validator\ValidatorExtension($validator),
			new \Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension($managerRegistry),
			new FormSupraExtension($metadataFactory),
		), (array) $this->extensions);

		$resolvedFormTypeFactory = new Form\ResolvedFormTypeFactory;
		
		$formRegistry = new Form\FormRegistry($extensions, $resolvedFormTypeFactory);
		$formFactory = new Form\FormFactory($formRegistry, $resolvedFormTypeFactory);

		ObjectRepository::setFormFactory($this->caller, $formFactory, 'Symfony\Component\Form\FormFactoryInterface');
	}
}
