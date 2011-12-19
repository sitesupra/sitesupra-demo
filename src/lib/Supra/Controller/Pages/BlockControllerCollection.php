<?php

/**
 * BlockControllerCollection
 *
 * @author Dmitry Polovka <dmitry.polovka@videinfra.com>
 */
namespace Supra\Controller\Pages;

use Supra\Controller\Pages\Exception;
use Supra\Controller\Pages\Configuration\BlockControllerConfiguration;
use Supra\Loader\Loader;

/**
 * Singleton storing all block configuration
 */
class BlockControllerCollection
{
	/**
	 * @var array 
	 */
	protected $configuration = array();
	
	/**
	 * @var BlockControllerCollection 
	 */
	protected static $instance;

	/**
	 * @return BlockControllerCollection 
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new BlockControllerCollection();
		}
		return self::$instance;
	}

	/**
	 * @return array 
	 */
	public function getConfigurationList()
	{
		return $this->configuration;
	}
	
	/**
	 * @param string $blockId
	 * @return BlockControllerConfiguration 
	 */
	public function getConfiguration($blockId)
	{
		$blockId = 	str_replace('\\', '_', $blockId);
		
		return $this->configuration[$blockId];
	}

	/**
	 * @param BlockControllerConfiguration $configuration 
	 */
	public function addConfiguration(BlockControllerConfiguration $configuration)
	{
		$blockId = 	str_replace('\\', '_', $configuration->id);
		
		$this->configuration[$blockId] = $configuration;
	}
	
	/**
	 * @param string $controllerClass
	 * @return BlockController 
	 */
	public function getBlockController($blockId)
	{
		$configuration = $this->getConfiguration($blockId);
		$controllerClass = $configuration->controllerClass;
		
		if ( ! class_exists($controllerClass)) {
			throw new Exception\RuntimeException('Class "' . $controllerClass . '" does not exist');
		}
		
		/* @var $controller BlockController */
		$controller = Loader::getClassInstance($controllerClass, 'Supra\Controller\Pages\BlockController');
		
		$controller->setConfiguration($configuration);
		
		return $controller;
	}
}