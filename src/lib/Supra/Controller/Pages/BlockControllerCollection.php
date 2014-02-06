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
	protected $configurationProxies = array();
	
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
		if ( ! empty($this->configurationProxies)) {
			foreach ($this->configurationProxies as $proxy) {
				
				$this->unproxyProxy($proxy);
			}
		}
		
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
	public function createBlockController($blockId)
	{
//		$configuration = ObjectRepository::getComponentConfiguration($blockId);

		$configuration = $this->getControllerConfiguration($blockId);
		
		if ( ! Loader::classExists($configuration->class)) {
			
			$configuration = $this->configuration['blocks'][BrokenBlockController::BLOCK_NAME];
			
			if (is_null($configuration)) {
				throw new Exception\ConfigurationException("The broken block controller is not configured.");
			}
		}
		
		if ( ! $configuration instanceof BlockControllerConfiguration) {
			throw new Exception\ConfigurationException("Configuration for block ID '$blockId' name must be block controller configuration");
		}
		
		$controller = $configuration->createBlockController();
		
		return $controller;
	}
	
	/**
	 * @param BlockControllerConfiguration $configuration 
	 */
	public function addBlockConfigurationProxy(Configuration\BlockControllerConfigurationProxy $proxy)
	{
		$controllerName = $proxy->getControllerName();
		$this->configurationProxies[$controllerName] = $proxy;
	}
	
	/**
	 * 
	 * @param string $controllerId
	 * @return BlockControllerConfiguration
	 * @throws Exception\ConfigurationException
	 */
	public function getControllerConfiguration($controllerId)
	{
		if (isset($this->configurationProxies[$controllerId])) {
			$this->unproxyProxy($this->configurationProxies[$controllerId]);
		}
		
		return ObjectRepository::getComponentConfiguration($controllerId);
	}
	
	/**
	 * @param Configuration\BlockControllerConfigurationProxy $proxy
	 */
	protected function unproxyProxy(Configuration\BlockControllerConfigurationProxy $proxy)
	{
		$controllerName = $proxy->getControllerName();
		
		$proxy->load();
		
		unset($this->configurationProxies[$controllerName]);
	}
}
