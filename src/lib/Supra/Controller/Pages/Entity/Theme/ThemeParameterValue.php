<?php

namespace Supra\Controller\Pages\Entity\Theme;

use Supra\Database;

/**
 * @Entity 
 * @ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * @Table(name="ThemeParameterValue", uniqueConstraints={@UniqueConstraint(name="unique_name_in_set_idx", columns={"parameterName", "set_id"})}))
 */
class ThemeParameterValue extends Database\Entity
{

	/**
	 * @ManyToOne(targetEntity="ThemeParameterSet", inversedBy="values")
	 * @JoinColumn(name="set_id", referencedColumnName="id")
	 * @var ThemeParameterSet
	 */
	protected $set;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $parameterName;

	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $value;

	/**
	 * kludge
	 */
	protected $parameter;

	public function __clone()
	{
		$this->regenerateId();
	}

	/**
	 * @return ThemeParameterSet
	 */
	public function getSet()
	{
		return $this->set;
	}

	/**
	 * @param ThemeParameterSet $set 
	 */
	public function setSet(ThemeParameterSet $set = null)
	{
		$this->set = $set;
	}

	/**
	 * @return string
	 */
	public function getParameterName()
	{
		return $this->parameterName;
	}

	/**
	 * @param string $parameterName 
	 */
	public function setParameterName($parameterName)
	{
		$this->parameterName = $parameterName;
	}

	/**
	 * @return string
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param string $value 
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}

}
