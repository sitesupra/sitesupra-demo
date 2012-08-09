<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Database;
use Supra\Controller\Pages\Entity\ThemeParameterValue;

/**
 * @Entity
 * @ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * @Table(uniqueConstraints={@UniqueConstraint(name="unique_name_in_theme_idx", columns={"name", "theme_id"})}))
 */
class ThemeParameter extends Database\Entity
{

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $title;

	/**
	 * @ManyToOne(targetEntity="Theme", inversedBy="parameters")
	 * @JoinColumn(name="theme_id", referencedColumnName="id")
	 * @var Theme
	 */
	protected $theme;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $type;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $defaultValue;

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name 
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $name 
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}

	/**
	 * @return Theme
	 */
	public function getTheme()
	{
		return $this->theme;
	}

	/**
	 * @param Theme $theme 
	 */
	public function setTheme(Theme $theme = null)
	{
		$this->theme = $theme;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param string $name 
	 */
	public function setType($type)
	{
		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function getDefaultValue()
	{
		return $this->defaultValue;
	}

	/**
	 * @param string $name 
	 */
	public function setDefaultValue($defaultValue)
	{
		$this->defaultValue = $defaultValue;
	}

	/**
	 * @return \Supra\Controller\Pages\Entity\ThemeParameterValue 
	 */
	public function getThemeParameterValue()
	{
		$parameterValue = new ThemeParameterValue();

		$parameterValue->setParameterName($this->getName());
		$parameterValue->setValue($this->getDefaultValue());

		return $parameterValue;
	}

	/**
	 * 
	 */
	public function getConfiguration()
	{
		$themeConfiguration = $this->getTheme()->getConfiguration();

		$configuration = null;

		foreach ($themeConfiguration->parameters as $someParameterConfiguration) {

			if ($someParameterConfiguration->name == $this->name) {
				$configuration = $someParameterConfiguration;
				break;
			}
		}

		return $configuration;
	}

}
