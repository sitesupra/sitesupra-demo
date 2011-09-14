<?php

/**
 * BlockConfiguration
 *
 * @author Dmitry Polovka <dmitry.polovka@videinfra.com>
 */
namespace Supra\Controller\Pages\Configuration;

use Supra\Controller\Pages\BlockControllerCollection;

class BlockControllerConfiguration
{
	public $id;
	public $title;
	public $description;
	public $icon = 'icon.png';
	public $classname;

	public $controllerClass;
	
	public function configure()
	{
		if(empty($this->id)) {
			$id = str_replace('\\', '_', $this->controllerClass);
			$this->id = $id;
		}
		
		BlockControllerCollection::getInstance()
				->addConfiguration($this);
	}
	
	
	public function getIconWebPath()
	{
		$file = \Supra\Loader\Loader::getInstance()->findClassPath($this->controllerClass);
		$dir = dirname($file);
		$iconPath = $dir . '/' . $this->icon;

		if (strpos($iconPath, SUPRA_WEBROOT_PATH) === 0) {
			$iconPath = substr($iconPath, strlen(SUPRA_WEBROOT_PATH) - 1);
		} else {
			$iconPath = null;
		}
		
		return $iconPath;
	}
}