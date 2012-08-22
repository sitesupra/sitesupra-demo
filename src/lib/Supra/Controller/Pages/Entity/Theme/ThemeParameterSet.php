<?php

namespace Supra\Controller\Pages\Entity\Theme;

use Supra\Database;
use Doctrine\Common\Collections\ArrayCollection;
use Supra\Controller\Pages\Entity\Theme\Parameter\ThemeParameterAbstraction;

/**
 * @Entity 
 * @ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * @Table(uniqueConstraints={@UniqueConstraint(name="unique_name_in_theme_idx", columns={"name", "theme_id"})}))
 */
class ThemeParameterSet extends Database\Entity
{

	const TYPE_PRESET = 'preset';
	const TYPE_USER = 'user';

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
	 * @Column(type="string")
	 * @var boolean
	 */
	protected $type = self::TYPE_PRESET;

	/**
	 * @OneToMany(targetEntity="ThemeParameterValue", mappedBy="set", cascade={"all"}, orphanRemoval=true, indexBy="parameterName")
	 * @var ArrayCollection
	 */
	protected $values;

	public function __construct()
	{
		parent::__construct();

		$this->values = new ArrayCollection();
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
		if ($this->values->containsKey($value->getParameterName())) {
			$this->removeValue($this->values->get($value->getParameterName()));
		}

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

	/**
	 * @param \Supra\Controller\Pages\Entity\Theme\Parameter\ThemeParameterAbstraction $parameter
	 * @return ThemeParameterValue
	 */
	public function addNewValueForParameter(Parameter\ThemeParameterAbstraction $parameter)
	{
		$value = $parameter->makeNewParameterValue();

		$this->addValue($value);

		return $value;
	}

	/**
	 * @return array
	 */
	public function getOutputValues()
	{
		$theme = $this->getTheme();
		$parameters = $theme->getParameters();

		$outputValues = array();

		foreach ($parameters as $parameter) {
			/* @var $parameter ThemeParameterAbstraction */

			$parameterValue = $this->getParameterValueForParameter($parameter);

			$outputValues[$parameter->getName()] = $parameter->getOuptutValueFromParameterValue($parameterValue);
		}

		return $outputValues;
	}

	/**
	 * @param ThemeParameterValue $parameter
	 * @return mixed
	 */
	protected function getParameterValueForParameter(ThemeParameterAbstraction $parameter)
	{
		$parameterName = $parameter->getName();

		$parameterValue = $this->values->get($parameterName);

		return $parameterValue;
	}

	/**
	 * @return array
	 */
	public function getOutputValuesForLess()
	{
		$theme = $this->getTheme();
		$parameters = $theme->getParameters();

		foreach ($parameters as $parameter) {
			/* @var $parameter ThemeParameterAbstraction */

			if ($parameter->hasValueForLess()) {

				$parameterValue = $this->getParameterValueForParameter($parameter);

				$outputValueForLess = $parameter->getLessOuptutValueFromParameterValue($parameterValue);
				$parameterName = $parameter->getName();

				if (is_array($outputValueForLess)) {

					foreach ($outputValueForLess as $key => $value) {
						$flatOutputValuesForLess[$parameterName . '_' . $key] = $value;
					}
				} else {

					$flatOutputValuesForLess[$parameterName] = $outputValueForLess;
				}
			}
		}

		return $flatOutputValuesForLess;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param string $type
	 */
	public function setType($type)
	{
		$this->type = $type;
	}

}
