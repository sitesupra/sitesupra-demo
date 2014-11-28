<?php

namespace Supra\Package\Cms\Pages\Block;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Templating\TwigTemplating;
use Supra\Package\Cms\Entity\Abstraction\Block;
use Supra\Package\Cms\Pages\Block\BlockConfiguration;
use Supra\Package\Cms\Pages\Block\UnknownBlockController;
use Supra\Package\Cms\Pages\BlockController;

class BlockCollection implements ContainerAware
{
	/**
	 * @var array
	 */
	protected $blockConfigurations = array();

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

		$this->addConfiguration(new UnknownBlockConfiguration());
	}

	/**
	 * @param mixed $blockConfigurations
	 */
	public function add($blockConfigurations)
	{
		if (! is_array($blockConfigurations)) {
			$blockConfigurations = array($blockConfigurations);
		}

		foreach ($blockConfigurations as $config) {
			$this->addConfiguration($config);
		}
	}

	/**
	 * @param BlockConfiguration $configuration
	 * @throws \RuntimeException
	 */
	public function addConfiguration(BlockConfiguration $configuration)
	{
		$className = $configuration->getControllerClass();

		if (isset($this->blockConfigurations[$className])) {
			throw new \RuntimeException(
					"Configuration for block [{$className}] already is in collection."
			);
		}

		$this->blockConfigurations[$className] = $configuration;
	}

	/**
	 * @param string $className
	 * @return BlockConfiguration
	 * @throws \RuntimeException
	 */
	public function getConfiguration($className)
	{
		$configuration = isset($this->blockConfigurations[$className])
				? $this->blockConfigurations[$className]
				: $this->blockConfigurations[UnknownBlockController::className]
				;

		if (! $configuration->isInitialized()) {
			$this->initialize($configuration);
		}

		return $configuration;
	}

	/**
	 * @param string $className
	 * @return bool
	 */
	public function hasConfiguration($className)
	{
		return isset($this->blockConfigurations[$className]);
	}

	/**
	 * @return BlockConfiguration[]
	 */
	public function getConfigurations()
	{
		foreach ($this->blockConfigurations as $config) {
			if (! $config->isInitialized()) {
				$this->initialize($config);
			}
		}

		return $this->blockConfigurations;
	}

	/**
	 * @param Block $block
	 * @return BlockController
	 */
	public function createController(Block $block)
	{
		$componentClass = $block->getComponentClass();

		$configuration = $this->getConfiguration($componentClass);

		$reflection = new \ReflectionClass($configuration->getControllerClass());

		$controller = $reflection->newInstance($block, $configuration);

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

	/**
	 * @TODO: should have some BlockBuilder? BlockConfigurationBuilder? instead
	 *
	 * @param BlockConfiguration $configuration
	 */
	protected function initialize(BlockConfiguration $configuration)
	{
		$templating = $this->container->getTemplating();

		if (! $templating instanceof TwigTemplating) {
			throw new \RuntimeException('Twig templating engine is required.');
		}

		$configuration->initialize($templating->getTwig());
	}
}
