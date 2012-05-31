<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Database;
use Doctrine\Common\Collections\ArrayCollection;
use Supra\Controller\Pages\Entity\ThemeParameterValue;
use Supra\Controller\Pages\Entity\ThemeParameter;

/**
 * @Entity 
 * @ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * @Table(uniqueConstraints={@UniqueConstraint(name="unique_name_in_theme_idx", columns={"name", "theme_id"})}))
 */
class ThemeParameterSet extends Database\Entity
{

	/**
	 * @ManyToOne(targetEntity="Theme", inversedBy="parameterSets", fetch="EAGER")
	 * @JoinColumn(name="theme_id", referencedColumnName="id")
	 * @var Theme
	 */
	protected $theme;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;

	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $title;

	/**
	 * @Column(type="boolean")
	 * @var boolean
	 */
	protected $locked = false;

	/**
	 * @OneToMany(targetEntity="ThemeParameterValue", mappedBy="set", cascade={"all"}, orphanRemoval=true, indexBy="parameterName")
	 * @var ArrayCollection
	 */
	protected $values;

	/**
	 * @var ArrayCollection
	 */
	protected $removedValues;

	public function __construct()
	{
		parent::__construct();

		$this->values = new ArrayCollection();
		$this->removedValues = new ArrayCollection();
	}

	/**
	 * @return array 
	 */
	public function getOutputValues()
	{
		$values = array();

		foreach ($this->values as $value) {
			/* @var $value ThemeParameterValue */

			$name = $value->getParameterName();

			$values[$name] = $value->getOutputValue();
		}

		return $values;
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
	 * @param string $title 
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}

	/**
	 * @return boolean
	 */
	public function isLocked()
	{
		return $this->locked;
	}

	/**
	 * @param boolean $locked 
	 */
	public function setLocked($locked)
	{
		$this->locked = $locked;
	}

	/**
	 * @return array
	 */
	public function getValues()
	{
		return $this->values;
	}

	/**
	 * @param array $values 
	 */
	public function setValues($values)
	{
		$this->values = $values;
	}

	/**
	 * @param ThemeParameterValue $value 
	 */
	public function addValue(ThemeParameterValue $value)
	{
		$value->setSet($this);
		$this->values[$value->getParameterName()] = $value;
	}

	/**
	 * @param ThemeParameterValue $value 
	 */
	public function removeValue(ThemeParameterValue $value)
	{
		$value->setSet(null);

		$this->values->removeElement($value);
	}

}
