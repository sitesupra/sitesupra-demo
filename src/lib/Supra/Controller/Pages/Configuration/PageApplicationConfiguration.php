<?php

namespace Supra\Controller\Pages\Configuration;

use Supra\Controller\Pages\Application\PageApplicationCollection;
use Supra\Configuration\ConfigurationInterface;

/**
 * Configuration for page applications, works as 
 */
class PageApplicationConfiguration implements ConfigurationInterface
{

	/**
	 * @var string
	 */
	public $id;

	/**
	 * @var string
	 */
	public $className;

	/**
	 * @var string
	 */
	public $title;

	/**
	 * @var string
	 */
	public $icon;

	/**
	 * If is set to true, then when performing drag&drop on app folder new record will be prepended.
	 * @var boolean 
	 */
	public $newChildrenFirst = false;

	/**
	 * @var boolean 
	 */
	public $isDragable = true;

	/**
	 * @var boolean 
	 */
	public $isDropTarget = true;

	public function configure()
	{
		PageApplicationCollection::getInstance()
				->addConfiguration($this);
	}

}
