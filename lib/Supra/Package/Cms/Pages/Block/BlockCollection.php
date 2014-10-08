<?php

namespace Supra\Package\Cms\Pages\Block;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\Cms\Entity\Abstraction\Block;
use Supra\Package\Cms\Pages\Block\BlockConfiguration;

class BlockCollection implements ContainerAware
{
	/**
	 * @var array
	 */
	protected $blocks = array();

	/**
	 * @var array
	 */
	protected $groups = array();

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @param array $groups
	 * @param array $blocks
	 */
	public function __construct(array $groups = array(), array $blocks = array())
	{
		foreach ($groups as $group) {
			$this->addGroupConfiguration($group);
		}

		foreach ($blocks as $block) {
			$this->addConfiguration($block);
		}
	}

	/**
	 * @param BlockConfiguration $configuration
	 * @throws \RuntimeException
	 */
	public function addConfiguration(BlockConfiguration $configuration)
	{
		$className = $configuration->getControllerClass();

		if (isset($this->blocks[$className])) {
			throw new \RuntimeException(
					"Configuration for block [{$className}] already is in collection."
			);
		}

		$this->blocks[$className] = $configuration;
	}

	/**
	 * @param string $className
	 * @return BlockConfiguration
	 * @throws \RuntimeException
	 */
	public function getConfiguration($className)
	{
		if (! isset($this->blocks[$className])) {
			throw new \RuntimeException(
					"Missing configuration for block [{$className}]"
			);
		}

		$this->blocks[$className]->initialize();

		return $this->blocks[$className];
	}

	/**
	 * @return BlockConfiguration[]
	 */
	public function getConfigurations()
	{
		foreach ($this->blocks as $configuration) {
			$configuration->initialize();
		}

		return $this->blocks;
	}

	/**
	 * @param Block $block
	 * @return BlockController
	 */
	public function createController(Block $block)
	{
		$controllerClass = $block->getComponentClass();

		$configuration = $this->getConfiguration($controllerClass);

		$controller = new $controllerClass($block, $configuration);

		$controller->setContainer($this->container);

		return $controller;
	}

	/**
	 * @param BlockGroupConfiguration $configuration
	 * @throws \RuntimeException
	 * @throws \LogicException
	 */
	public function addGroupConfiguration(BlockGroupConfiguration $configuration)
	{
		$groupName = $configuration->getName();

		if (isset($this->groups[$groupName])) {
			throw new \RuntimeException(
					"Configuration for group [{$groupName}] already is in collection."
			);
		}

		if ($configuration->isDefault()
				&& ($default = $this->findDefaultGroupConfiguration()) !== null) {
			
			throw new \LogicException("Group [{$default->getName()}] is already is set as default.");
		}

		$this->groups[$groupName] = $configuration;
	}

	/**
	 * @param string $groupName
	 * @return BlockGroupConfiguration
	 * @throws \RuntimeException
	 */
	public function getGroupConfiguration($groupName)
	{
		if (! isset($this->groups[$groupName])) {
			throw new \RuntimeException(
					"Group [{$groupName}] is not defined."
			);
		}

		return $this->groups[$groupName];
	}

	/**
	 * @return BlockGroupConfiguration
	 */
	public function getGroupConfigurations()
	{
		return $this->groups;
	}

	/**
	 * @return BlockGroupConfiguration | null
	 */
	public function findDefaultGroupConfiguration()
	{
		foreach ($this->groups as $group) {
			if ($group->isDefault()) {
				return $group;
			}
		}

		return null;
	}
	
	/**
	 * @param ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

//	/**
//	 * @param string $blockId
//	 * @return BlockController
//	 */
//	public function createBlockController($blockId)
//	{
////		$configuration = ObjectRepository::getComponentConfiguration($blockId);
//
//		$configuration = $this->getControllerConfiguration($blockId);
//
//		if ( ! Loader::classExists($configuration->class)) {
//
//			$configuration = $this->configuration['blocks'][BrokenBlockController::BLOCK_NAME];
//
//			if (is_null($configuration)) {
//				throw new Exception\ConfigurationException("The broken block controller is not configured.");
//			}
//		}
//
//		if ( ! $configuration instanceof BlockControllerConfiguration) {
//			throw new Exception\ConfigurationException("Configuration for block ID '$blockId' name must be block controller configuration");
//		}
//
//		$controller = $configuration->createBlockController();
//
//		return $controller;
//	}
}
