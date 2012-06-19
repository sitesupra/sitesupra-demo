<?php

namespace Supra\Controller\Pages\Plugin;

use Supra\Configuration\ConfigurationInterface;
use Supra\Controller\Pages\BlockController;
use Supra\ObjectRepository\ObjectRepository;

/**
 * BlockControllerPluginLoad
 */
class BlockControllerPluginLoad extends BlockControllerPlugin
{
	public function configure()
	{
		
	}
	
	public function bind(BlockController $blockController)
	{
		$object = ObjectRepository::getObject($this->id, BlockControllerPlugin::CN);
		/* @var $object BlockControllerPlugin */
		
		$object->bind($blockController);
		
	}
}
