<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace Supra\Package\Cms\Pages\Block;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Package\AbstractSupraPackage;
use Supra\Core\Templating\TwigTemplating;
use Supra\Package\Cms\Entity\Abstraction\Block;
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
	 * @param null|AbstractSupraPackage $package
	 */
	public function __construct(array $groups = array(), array $blocks = array(), AbstractSupraPackage $package = null)
	{
		foreach ($groups as $group) {
			$this->addGroupConfiguration($group);
		}

		foreach ($blocks as $block) {
			$this->addConfig($block, $package);
		}

		$this->addConfig(new UnknownBlockConfig(), $package);
	}

	/**
	 * @param mixed $blockConfigurations
	 * @param null|AbstractSupraPackage $package
	 */
	public function add($blockConfigurations, AbstractSupraPackage $package = null)
	{
		if (! is_array($blockConfigurations)) {
			$blockConfigurations = array($blockConfigurations);
		}

		foreach ($blockConfigurations as $config) {
			$this->addConfig($config, $package);
		}
	}

	/**
	 * @param Config\BlockConfig $config
	 * @param AbstractSupraPackage $package
	 * @throws \RuntimeException
	 */
	public function addConfig(Config\BlockConfig $config, AbstractSupraPackage $package = null)
	{
		$className = get_class($config);

		if (isset($this->blockConfigurations[$className])) {
			throw new \RuntimeException(
					"Configuration for block [{$className}] already is in collection."
			);
		}

		if ($package !== null) {
			$config->setPackage($package);
		}

		$this->blockConfigurations[$className] = $config;
	}

	/**
	 * @param string $className
	 * @return Config\BlockConfig
	 * @throws \RuntimeException
	 */
	public function getConfiguration($className)
	{
		$config = isset($this->blockConfigurations[$className])
				? $this->blockConfigurations[$className]
				: $this->blockConfigurations[__NAMESPACE__ . '\\UnknownBlockConfig']
				;

		/* @var $config Config\BlockConfig */

		if (! $config->isInitialized()) {
			$this->initialize($config);
		}

		return $config;
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
	 * @return Config\BlockConfig[]
	 */
	public function getConfigurations()
	{
		foreach ($this->blockConfigurations as $config) {
			/* @var $config Config\BlockConfig */
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
			/* @var $group BlockGroupConfiguration */
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

	/**
	 * @TODO: should have some BlockBuilder? BlockConfigurationBuilder? instead
	 *
	 * @param Config\BlockConfig $config
	 */
	protected function initialize(Config\BlockConfig $config)
	{
		$templating = $this->container->getTemplating();

		if (! $templating instanceof TwigTemplating) {
			throw new \RuntimeException('Twig templating engine is required.');
		}

		$config->initialize($templating->getTwig());
	}
}
