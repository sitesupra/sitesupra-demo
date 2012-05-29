<?php

namespace Supra\Controller\Pages;

use Supra\Controller\Pages\Exception;
use Supra\Controller\Pages\Configuration\BlockControllerConfiguration;
use Supra\Controller\Pages\Configuration\BlockControllerGroupConfiguration;
use Supra\Loader\Loader;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\BrokenBlockController;

/**
 * Singleton storing collection if block configuration and block groups
 *
 * @author Dmitry Polovka <dmitry.polovka@videinfra.com>
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
	 * @param BlockControllerConfiguration $configuration 
	 */
	public function addBlockConfiguration(BlockControllerConfiguration $configuration)
	{
		$blockId = $configuration->id;

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
	 * @param string $blockId
	 * @return BlockController 
	 */
	public function getBlockController($blockId)
	{
		$configuration = ObjectRepository::getComponentConfiguration($blockId);

		if ( ! Loader::classExists($configuration->class)) {
			$configuration = $this->configuration['blocks'][BrokenBlockController::BLOCK_NAME];
		}
		
		$controllerClass = $configuration->class;
		$controller = null;

		try {
			/* @var $controller BlockController */
			$controller = Loader::getClassInstance($controllerClass, 'Supra\Controller\Pages\BlockController');
			$controller->setConfiguration($configuration);
		} catch (\Exception $e) {
			$controllerClass = 'Supra\Controller\Pages\NotInitializedBlockController';
			$controller = Loader::getClassInstance($controllerClass, 'Supra\Controller\Pages\BlockController');
			/* @var $controller BlockController */
			$controller->exception = $e;
			$controller->setConfiguration($configuration);
		}
		
		return $controller;
	}
	
}
