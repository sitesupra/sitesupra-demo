<?php

/**
 * Collection of blocks and block groups
 *
 * @author Dmitry Polovka <dmitry.polovka@videinfra.com>
 */

namespace Supra\Controller\Pages;

use Supra\Controller\Pages\Exception;
use Supra\Controller\Pages\Configuration\BlockControllerConfiguration;
use Supra\Controller\Pages\Configuration\BlockControllerGroupConfiguration;
use Supra\Loader\Loader;

/**
 * Singleton storing all block configuration
 */
class BlockControllerCollection
{

	/**
	 * @var array 
	 */
	protected $configuration = array(
		'blocks' => array(),
		'groups' => array(),
	);

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
	public function getBlocksConfigurationList()
	{
		return $this->configuration['blocks'];
	}

	/**
	 * @param string $blockId
	 * @return BlockControllerConfiguration 
	 */
	public function getBlockConfiguration($blockId)
	{
		$blockId = str_replace('\\', '_', $blockId);

		return $this->configuration['blocks'][$blockId];
	}

	/**
	 * @param BlockControllerConfiguration $configuration 
	 */
	public function addBlockConfiguration(BlockControllerConfiguration $configuration)
	{
		$blockId = str_replace('\\', '_', $configuration->id);

		$this->configuration['blocks'][$blockId] = $configuration;
	}

	/**
	 * @return array 
	 */
	public function getGroupsConfigurationList()
	{
		return $this->configuration['groups'];
	}

	/**
	 * @param string $groupId
	 * @return BlockControllerGroupConfiguration
	 */
	public function getGroupConfiguration($groupId)
	{
		$groupId = str_replace(' ', '-', trim($groupId));

		return $this->configuration['groups'][$groupId];
	}

	/**
	 * @param BlockControllerGroupConfiguration $configuration 
	 */
	public function addGroupConfiguration(BlockControllerGroupConfiguration $configuration)
	{
		if (empty($configuration->id)) {
			throw new Exception\ConfigurationException('Group id can not be empty');
		}

		$groupId = str_replace(' ', '-', trim($configuration->id));

		$this->configuration['groups'][$groupId] = $configuration;
	}

	/**
	 * @param string $controllerClass
	 * @return BlockController 
	 */
	public function getBlockController($blockId)
	{
		$configuration = $this->getBlockConfiguration($blockId);
		$controllerClass = $configuration->controllerClass;

		if ( ! class_exists($controllerClass)) {
			//throw new Exception\RuntimeException('Class "' . $controllerClass . '" does not exist');
			//throw new Exception\InvalidBlockException('Class "' . $controllerClass . '" does not exist');
			$controllerClass = 'Supra\Controller\Pages\MissingBlockController';
			$configuration = new BlockControllerConfiguration();
		}

		/* @var $controller BlockController */

		$controller = $controllerClass::createController();

		//$controller = Loader::getClassInstance($controllerClass, 'Supra\Controller\Pages\BlockController');

		$controller->setConfiguration($configuration);

		return $controller;
	}

}