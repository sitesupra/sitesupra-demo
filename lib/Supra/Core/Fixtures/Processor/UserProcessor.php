<?php

namespace Supra\Core\Fixtures\Processor;

use Nelmio\Alice\ProcessorInterface;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\CmsAuthentication\Entity\User;

class UserProcessor implements ProcessorInterface, ContainerAware
{
	protected $container;

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}


	public function preProcess($object)
	{
		if ($object instanceof User) {
			$encoder = $this->container['cms_authentication.encoder_factory']->getEncoder($object);

			$object->setPassword($encoder->encodePassword($object->getPassword(), $object->getSalt()));
		}
	}

	public function postProcess($object) {}

}