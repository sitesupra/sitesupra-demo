<?php

namespace Supra\Controller\Pages\Plugin;

use Supra\Configuration\ConfigurationInterface;
use Supra\Controller\Pages\BlockController;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Plugin with possibility to be bound to
 */
abstract class BlockControllerPlugin implements ConfigurationInterface
{
	const CN = __CLASS__;
	
	public $id;
	
	public function configure()
	{
		if ( ! empty($this->id)) {
			ObjectRepository::setObject($this->id, $this, BlockControllerPlugin::CN);
		}
	}
	
	abstract public function bind(BlockController $blockController);
}
