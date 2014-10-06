<?php

namespace Supra\Package\CmsAuthentication;

use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Event\KernelEvent;
use Supra\Core\Package\AbstractSupraPackage;
use Supra\Core\Package\PackageLocator;
use Supra\Package\CmsAuthentication\Application\CmsAuthenticationApplication;
use Supra\Package\CmsAuthentication\Event\Listener\CmsAuthenticationRequestListener;
use Supra\Package\CmsAuthentication\Event\Listener\CmsAuthenticationResponseListener;
use Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\SecurityContext;

class SupraPackageCmsAuthentication extends AbstractSupraPackage
{
	public function inject(ContainerInterface $container)
	{
		$configuration = $this->loadConfiguration($container);

		$container[$this->name.'.request_listener'] = function () {
			return new CmsAuthenticationRequestListener();
		};

		$container[$this->name.'.response_listener'] = function () {
			return new CmsAuthenticationResponseListener();
		};

		$container->getEventDispatcher()
			->addListener(KernelEvent::REQUEST, array($container[$this->name.'.request_listener'], 'listen'));
		$container->getEventDispatcher()
			->addListener(KernelEvent::RESPONSE, array($container[$this->name.'.response_listener'], 'listen'));

		//routing
		$container->getRouter()->loadConfiguration(
			$container->getApplication()->locateConfigFile($this, 'routes.yml')
		);

		//applications
		$container->getApplicationManager()->registerApplication(new CmsAuthenticationApplication());

		//we need to inject shared users em to doctrine
		$doctrineConfig = $container->getApplication()->getConfigurationSection('framework');

		$doctrineConfig['doctrine']['entity_managers']['shared'] = array('connection' => 'shared', 'event_manager' => 'public');

		$doctrineConfig['doctrine']['connections']['shared'] = $configuration['users']['shared_connection'];

		$container->getApplication()->setConfigurationSection('framework', $doctrineConfig);
	}

	public function finish(ContainerInterface $container)
	{
		$container['cms_authentication.users.voters'] = function (ContainerInterface $container) {
			$voters = array();

			foreach ($container->getParameter('cms_authentication.users.voters') as $id) {
				$voters[] = $container[$id];
			}

			return $voters;
		};

		$container['cms_authentication.users.access_decision_manager'] = function (ContainerInterface $container) {
			return new AccessDecisionManager($container['cms_authentication.users.voters']);
		};

		$container['cms_authentication.users.authentication_manager'] = function (ContainerInterface $container) {
			$providers = array();

			foreach ($container->getParameter('cms_authentication.users.user_providers') as $type => $providersDefinition) {
				if ($type != 'doctrine') {
					throw new \Exception('Only "doctrine" user providers are allowed now');
				}

				foreach ($providersDefinition as $name => $providerDefinition) {
					$providers[] = $container->getDoctrine()->getManager($providerDefinition['em'])
						->getRepository($providerDefinition['entity']);
				}
			}

			return new AuthenticationProviderManager($providers);
		};

		$container['security.context'] = function (ContainerInterface $container) {
			return new SecurityContext(
				$container['cms_authentication.users.authentication_manager'],
				$container['cms_authentication.users.access_decision_manager']
			);
		};
	}


	/*$container['security.user_providers'] = function (ContainerInterface $container) {
			return array(
				$container['doctrine.entity_managers.public']->getRepository('CmsAuthentication:User'),
				$container['doctrine.entity_managers.shared']->getRepository('CmsAuthentication:User')
			);
		};

		$container['security.user_provider'] = function (ContainerInterface $container) {
			return new ChainUserProvider($container['security.user_providers']);
		};

		$container->setParameter('security.provider_key', 'cms_authentication');

		$userChecker = new UserChecker();

		//@todo: this should be moved to config
		$encoderFactory = new EncoderFactory(
			array(
				'Supra\Package\CmsAuthentication\Entity\User' => new SupraBlowfishEncoder()
			)
		);

		$providers = array(
			new AnonymousAuthenticationProvider(uniqid()),
			new DaoAuthenticationProvider(
				$container['security.user_provider'],
				$userChecker,
				$container->getParameter('security.provider_key'),
				$encoderFactory
			)
		);



		$container['security.voters'] = function () {
			return array(new RoleVoter()); //@todo: this should be refactored to acls
		};


		*/

}
