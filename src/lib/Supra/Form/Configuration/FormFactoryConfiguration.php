<?php

namespace Supra\Form\Configuration;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Validator;
use Symfony\Component\Form;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Supra\Configuration\ConfigurationInterface;
use Supra\Form\FormClassMetadataCache;
use Supra\Form\FormSupraExtension;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Form\Csrf\TokenStorage\SessionTokenStorage;

/**
 * Form factory configuration
 */
class FormFactoryConfiguration implements ConfigurationInterface
{
	public $caller = ObjectRepository::DEFAULT_KEY;

	/**
	 * @var array
	 */
	public $extensions = array();

	/**
	 * @var bool
	 */
	public $enableCsrfExtension = false;

	/**
	 * @return \Symfony\Component\Form\FormFactory
	 * @throws \RuntimeException
	 */
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

		if ($this->enableCsrfExtension) {

			$sessionManager = ObjectRepository::getObject(
					$this,
					ObjectRepository::INTERFACE_SESSION_NAMESPACE_MANAGER
			);

			if ($sessionManager === null) {
				throw new \RuntimeException('Csrf Extension requires SessionManager to be available
					for Supra\Form namespace.');
			}

			$sessionNamespace = $sessionManager->getSessionNamespace('_csrf');

			$storage = new SessionTokenStorage($sessionNamespace);
			$tokenManager = new CsrfTokenManager(null, $storage);

			$extensions[] = new Form\Extension\Csrf\CsrfExtension($tokenManager);
		}

		$resolvedFormTypeFactory = new Form\ResolvedFormTypeFactory;
		
		$formRegistry = new Form\FormRegistry($extensions, $resolvedFormTypeFactory);
		$formFactory = new Form\FormFactory($formRegistry, $resolvedFormTypeFactory);

		ObjectRepository::setFormFactory($this->caller, $formFactory);

		return $formFactory;
	}
}
