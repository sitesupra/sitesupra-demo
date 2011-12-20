<?php

namespace Supra\BannerMachine\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Supra\ObjectRepository\ObjectRepository;
use Supra\BannerMachine\BannerProvider;
use Supra\BannerMachine\Exception;
use Supra\BannerMachine\Configuration\BannerType\BannerTypeConfigurationAbstraction;
use Supra\BannerMachine\BannerMachineRedirector;
use Supra\Router\UriRouter;
use Supra\Controller\FrontController;
use Supra\BannerMachine\EventListener;
use Supra\FileStorage\FileEventArgs;

class ProviderConfiguration implements ConfigurationInterface
{

	public $id;
	public $types;
	public $redirectorPath;
	public $namespace;

	public function configure()
	{
		$provider = new BannerProvider();

		$types = array();

		foreach ($this->types as $typeConfiguration) {
			/* @var $typeConfiguration BannerTypeConfigurationAbstraction */

			$type = $typeConfiguration->getType();

			$types[$type->getId()] = $type;
		}
		$provider->setTypes($types);

		$provider->setRedirectorPath($this->redirectorPath);

		$router = new UriRouter();
		$router->setPath($this->redirectorPath);
		FrontController::getInstance()->route($router, BannerMachineRedirector::CN());

		ObjectRepository::setDefaultBannerProvider($provider);
		ObjectRepository::setBannerProvider('#' . $this->id, $provider);

		$eventListener = new EventListener();

		$fileStorage = ObjectRepository::getFileStorage($provider);
		
		$eventManager = ObjectRepository::getEventManager($fileStorage);
		$eventManager->listen(array(FileEventArgs::FILE_EVENT_PRE_DELETE), $eventListener);
	}

}

