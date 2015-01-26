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

namespace Supra\Package\Cms\Pages\Block\Config;

use Doctrine\Common\Util\Inflector;
use Supra\Core\Package\AbstractSupraPackage;
use Supra\Package\Cms\Pages\Twig\BlockPropertyNodeVisitor;
use Supra\Package\Cms\Pages\Block\Exception\NotInitializedConfigurationException;
use Supra\Package\Cms\Pages\Block\Mapper\AttributeMapper;
use Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper;
use Supra\Package\Cms\Pages\Block\Mapper\CacheMapper;

/**
 * Block configuration abstraction.
 */
abstract class BlockConfig
{
	protected $title;
	protected $description;
	protected $icon;
	protected $tooltip;
	protected $groupName;
	protected $insertable = true;
	protected $unique = false;
//	protected $cmsClassName = 'Editable';

	/**
	 * @var string
	 */
	protected $templateName;

	// cache configuration object
	protected $cache;

	/**
	 * @var string
	 */
	protected $controllerClass;

	/**
	 * @var bool
	 */
	protected $autoDiscoverProperties = true;

	/**
	 * @var AbstractPropertyConfig[]
	 */
	protected $properties = array();

	/**
	 * @var bool
	 */
	private $initialized = false;

	/**
	 * @var AbstractSupraPackage
	 */
	private $package;

	/**
	 * @return string
	 */
	public function getName()
	{
		return trim(str_replace('\\', '_', get_called_class()));
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		$this->validate();

		return $this->title;
	}

	/**
	 * @param string $title
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}

	/**
	 * @return string
	 */
	public function getDescription()
	{
		$this->validate();

		return $this->description;
	}

	/**
	 * @param string $description
	 */
	public function setDescription($description)
	{
		$this->description = $description;
	}

	/**
	 * @return string
	 */
	public function getIcon()
	{
		$this->validate();

		return $this->icon;
	}

	/**
	 * @param string $icon
	 */
	public function setIcon($icon)
	{
		$this->icon = $icon;
	}

	/**
	 * @return string
	 */
	public function getTooltip()
	{
		$this->validate();

		return $this->tooltip;
	}

	/**
	 * @param string $tooltip
	 */
	public function setTooltip($tooltip)
	{
		$this->tooltip = $tooltip;
	}

	/**
	 * Determines block appearance in CMS Insert block list.
	 * 
	 * @return bool
	 */
	public function isInsertable()
	{
		$this->validate();

		return $this->insertable === true;
	}

	/**
	 * @param bool $insertable
	 */
	public function setInsertable($insertable)
	{
		$this->insertable = $insertable;
	}

	/**
	 * @return bool
	 */
	public function isUnique()
	{
		$this->validate();

		return $this->unique === true;
	}

	/**
	 * @TODO: implement
	 *
	 * @param bool $unique
	 */
	public function setUnique($unique)
	{
		throw new \BadMethodCallException('Not supported.');
//		$this->unique = $unique;
	}

//	/**
//	 * Frontend.
//	 * CMS class name for the block.
//	 *
//	 * @return string
//	 */
//	public function getCmsClassName()
//	{
//		$this->validate();
//
//		return $this->cmsClassName;
//	}
//
//	/**
//	 * @param string $cmsClassName
//	 */
//	public function setCmsClassName($cmsClassName)
//	{
//		$this->cmsClassName = $cmsClassName;
//	}

	public function getGroupName()
	{
		$this->validate();

		return $this->groupName;
	}

	/**
	 * @param string $groupName
	 */
	public function setGroupName($groupName)
	{
		$this->groupName = $groupName;
	}

	/**
	 * @param string $autoDiscoverProperties
	 */
	public function setAutoDiscoverProperties($autoDiscoverProperties)
	{
		$this->autoDiscoverProperties = $autoDiscoverProperties;
	}

	/**
	 * @return bool
	 */
	public function isPropertyAutoDiscoverEnabled()
	{
		$this->validate();

		return $this->autoDiscoverProperties === true;
	}

	/**
	 * @param string $templateName
	 */
	public function setTemplateName($templateName)
	{
		$this->templateName = $templateName;
	}

	/**
	 * @return string
	 */
	public function getTemplateName()
	{
		$this->validate();

		if (empty($this->templateName) && $this->package !== null) {
			return $this->guessTemplateName();
		}

		return $this->templateName;
	}

	/**
	 * @param AbstractPropertyConfig $property
	 * @throws \LogicException
	 */
	public function addProperty(AbstractPropertyConfig $property)
	{
		$this->validate();

		if ($this->hasProperty($property->name)) {
			throw new \LogicException("Property [{$property->name}] is already in collection.");
		}
		
		$this->properties[$property->name] = $property;
	}

	/**
	 * @return AbstractPropertyConfig[]
	 */
	public function getProperties()
	{
		$this->validate();

		return $this->properties;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasProperty($name)
	{
		$this->validate();

		return isset($this->properties[$name]);
	}

	/**
	 * @param string $name
	 * @return AbstractPropertyConfig
	 */
	public function getProperty($name)
	{
		$this->validate();

		if (($pos = strrpos($name, '.')) !== false) {

			$parent = $this->getProperty(substr($name, 0, $pos));

			if ($parent instanceof PropertySetConfig) {
				return $parent->getSetItem(substr($name, $pos + 1));

			} elseif ($parent instanceof PropertyListConfig) {
				return $parent->getListItem();

			} else {
				throw new \UnexpectedValueException(sprintf(
						'Only sets and collections can have sub-properties, [%s] received.',
						get_class($parent)
				));
			}
		}

		if (! isset($this->properties[$name])) {
			throw new \RuntimeException(sprintf(
					'Property [%s] is not defined.',
					$name
			));
		}

		return $this->properties[$name];
	}

	/**
	 * @return string
	 * @throws \LogicException
	 */
	public function getControllerClass()
	{
		if (empty($this->controllerClass)) {
			return $this->guessControllerClass();
		}

		return $this->controllerClass;
	}

	/**
	 * Sets controller class name for block.
	 *
	 * @param string $controllerClass
	 */
	public function setControllerClass($controllerClass)
	{
		$this->controllerClass = $controllerClass;
	}

	/**
	 * @return CacheMapper
	 */
	public function getCache()
	{
		return $this->cache;
	}

	/**
	 * Concrete classes should override this to configure block attributes.
	 *
	 * @param AttributeMapper $mapper
	 */
	protected function configureAttributes(AttributeMapper $mapper)
	{
		
	}

	/**
	 * Concrete classes should override this to configure block properties.
	 *
	 * @param PropertyMapper $mapper
	 */
	protected function configureProperties(PropertyMapper $mapper)
	{
		
	}

	/**
	 * Concrete classes should override this to configure block cache.
	 *
	 * @param CacheMapper $mapper
	 */
	protected function configureCache(CacheMapper $mapper)
	{

	}

	/**
	 * Tries to guess block template name.
	 * @return string
	 */
	private function guessTemplateName()
	{
		if ($this->package === null) {
			throw new \RuntimeException(sprintf(
				'Block package is unknown, thus it is impossible to guess template name for block [%s].',
				$this->getTitle()
			));
		}

		$name = (new \ReflectionClass($this))->getShortName();

		if (($pos = strpos($name, 'Config')) !== false
			&& $pos === (strlen($name) - 6)) {

			$name = substr($name, 0, -6);
		}

		return $this->package->getName() . ':blocks' . DIRECTORY_SEPARATOR . Inflector::tableize($name) . '.html.twig';
	}

	/**
	 * Tries to guess Block controller class name if $className is empty.
	 *
	 * @return string
	 */
	private function guessControllerClass()
	{
		$calledClass = get_called_class();

		if (($pos = strpos($calledClass, 'Config')) !== false
				&& $pos === (strlen($calledClass) - 6)
				&& class_exists(($className = substr($calledClass, 0, -6)))) {

			return $className;
		}

		return  '\Supra\Package\Cms\Pages\Block\DefaultBlockController';
	}

	/**
	 * @param \Twig_Environment $twig
	 * @throws \LogicException
	 */
	public function initialize(\Twig_Environment $twig)
	{
		if ($this->initialized === true) {
			throw new \LogicException('Is initialized already.');
		}

		$this->initialized = true;
		
		$this->configureAttributes(new AttributeMapper($this));
		$this->configureProperties(new PropertyMapper($this));
		$this->configureCache(new CacheMapper($this));

		if ($this->autoDiscoverProperties) {

			$tokenStream = $twig->tokenize(
				$twig->getLoader()->getSource($this->getTemplateName())
			);

			$traverser = new \Twig_NodeTraverser($twig);
			$nodeVisitor = new BlockPropertyNodeVisitor(new PropertyMapper($this));

			$traverser->addVisitor($nodeVisitor);

			$traverser->traverse($twig->parse($tokenStream));
			
		}
	}

	/**
	 * @return bool
	 */
	public function isInitialized()
	{
		return $this->initialized;
	}

	/**
	 * @param AbstractSupraPackage $package
	 */
	public function setPackage(AbstractSupraPackage $package)
	{
		$this->package = $package;
	}

	/**
	 * @throws NotInitializedConfigurationException
	 */
	private function validate()
	{
		if ($this->initialized === false) {
			throw new NotInitializedConfigurationException();
		}
	}
}