<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Database;
use Layout\Exception;

/**
 * @Entity 
 * @Table(indexes={
 * 		@index(name="themeName_idx", columns={"themeName"}),
 * 		@index(name="themeNameSetName_idx", columns={"themeName", "setName"})
 * })
 */
class ThemeParameterValue extends Database\Entity
{

	const SET_NAME_ACTIVE = 'active';
	const SET_NAME_PREVIEW = 'preview';

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $themeName;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $setName = self::SET_NAME_ACTIVE;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;

	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $value;

	/**
	 * @var string
	 */
	protected $defaultValue;

	public function __clone()
	{
		$this->regenerateId();
	}

	public function getThemeName()
	{
		return $this->themeName;
	}

	public function setThemeName($themeName)
	{
		$this->themeName = $themeName;
	}

	public function getSetName()
	{
		return $this->setName;
	}

	public function setSetName($setName)
	{
		if ( ! in_array($setName, array(self::SET_NAME_ACTIVE, self::SET_NAME_PREVIEW))) {
			throw new Exception\RuntimeException('Theme parameter set name "' . $setName . '" is not recignized. Use SET_NAME_* constants from ' . __CLASS__);
		}

		$this->setName = $setName;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getValue()
	{
		return $this->value;
	}

	public function setValue($value)
	{
		$this->value = $value;
	}

	public function getDefaultValue()
	{
		return $this->defaultValue;
	}

	public function setDefaultValue($defaultValue)
	{
		$this->defaultValue = $defaultValue;
	}

}
