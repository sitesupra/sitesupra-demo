<?php

/**
 * BlockControllerCollection
 *
 * @author Dmitry Polovka <dmitry.polovka@videinfra.com>
 */
namespace Supra\Controller\Pages;

use Supra\Controller\Pages\Exception;
use Supra\Controller\Pages\Configuration\BlockControllerConfiguration;

/**
 * 
 */
class BlockControllerCollection
{

	/**
	 *
	 * @var array 
	 */
	protected $configuration = array();
	
	/**
	 *
	 * @var BlockControllerCollection 
	 */
	protected static $instance;

	/**
	 *
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
	 *
	 * @return array 
	 */
	public function getConfigurationList()
	{
		return $this->configuration;
	}
	
	/**
	 *
	 * @param string $controllerClass
	 * @return BlockControllerConfiguration 
	 */
	public function getConfiguration($controllerClass)
	{
		return $this->configuration[$className];
	}

	/**
	 *
	 * @param BlockControllerConfiguration $configuration 
	 */
	public function addConfiguration(BlockControllerConfiguration $configuration)
	{
		$className = $configuration->controllerClass;
		$this->configuration[$className] = $configuration;
		
	}
	
	/**
	 *
	 * @param string $controllerClass
	 * @return BlockController 
	 */
	public function getBlockController($controllerClass)
	{
		if ( ! class_exists($controllerClass)) {
			throw new Exception\RuntimeException('Class does not exist');
		}
		
		$controller = new $controllerClass;
		
		if ( ! $controller instanceof BlockController) {
			throw new Exception\RuntimeException('Controller is not instance of BlockController');
		}

		return $controller;
	}
}