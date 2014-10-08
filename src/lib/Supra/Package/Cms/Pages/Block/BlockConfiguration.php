<?php

namespace Supra\Package\Cms\Pages\Block;

use Supra\Package\Cms\Pages\Block\Mapper\BlockMapper;
use Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper;
use Supra\Package\Cms\Pages\Block\Mapper\CacheMapper;

abstract class BlockConfiguration
{
	protected $title;
	protected $description;
	protected $icon;
	protected $tooltip;
	protected $groupName;
	protected $insertable = true;
	protected $cmsClassName = 'Editable';

	// cache configuration object
	protected $cache;

	protected $controllerClass;
	protected $properties = array();

	private $initialized = false;

	/**
	 * @return string
	 */
	public function getTitle()
	{
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
	 * @return type
	 */
	public function getDescription()
	{
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
	 * Frontend.
	 * CMS classname for the block.
	 *
	 * @return string
	 */
	public function getCmsClassName()
	{
		return $this->cmsClassName;
	}

	/**
	 * @param string $cmsClassName
	 */
	public function setCmsClassName($cmsClassName)
	{
		$this->cmsClassName = $cmsClassName;
	}

	public function getGroupName()
	{
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
	 * @param BlockPropertyConfiguration $property
	 * @throws \LogicException
	 */
	public function addProperty(BlockPropertyConfiguration $property)
	{
		$name = $property->getName();

		if ($this->hasProperty($name)) {
			throw new \LogicException("Property [{$name}] is already in collection.");
		}
		
		$this->properties[$name] = $property;
	}

	/**
	 * @return BlockPropertyConfiguration[]
	 */
	public function getProperties()
	{
		$this->initialize();

		return $this->properties;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasProperty($name)
	{
		return isset($this->properties[$name]);
	}

	/**
	 * @param string $name
	 * @return BlockPropertyConfiguration
	 */
	public function getProperty($name)
	{
		$this->initialize();

		if ($this->hasProperty($name)) {
			return $this->properties[$name];
		}
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
	 * Returns controller class name safe to use on frontend as block ID.
	 * 
	 * @return string
	 */
	public function getControllerClassId()
	{
		return trim(str_replace('\\', '_', $this->getControllerClass()));
	}

	public function initialize()
	{
		if ($this->initialized) {
			return null;
		}
		
		$this->configureBlock(new BlockMapper($this));
		$this->configureProperties(new PropertyMapper($this));
		$this->configureCache(new CacheMapper($this));

		$this->initialized = true;
	}

	protected function configureBlock(BlockMapper $mapper)
	{

	}

	protected function configureProperties(PropertyMapper $mapper)
	{

	}

	protected function configureCache(CacheMapper $mapper)
	{
		
	}

	/**
	 * Tries to guess Block controller class name if $className is empty.
	 *
	 * @return string
	 * @throws \LogicException in case if guess failed.
	 */
	private function guessControllerClass()
	{
		$calledClass = get_called_class();

		if (($pos = strpos($calledClass, 'Configuration')) !== false
				&& $pos === (strlen($calledClass) - 13)
				&& class_exists(($className = substr($calledClass, 0, -13)))) {

			return $className;
		}

		throw new \LogicException(sprintf(
				'Failed to guess controller class for configuration [%s]',
				$calledClass
		));
	}
}