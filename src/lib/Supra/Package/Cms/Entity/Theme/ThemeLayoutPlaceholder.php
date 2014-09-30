<?php

namespace Supra\Package\Cms\Entity\Theme;

use Supra\Package\Cms\Entity\Abstraction\Entity;

/**
 * @Entity 
 * @ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * @Table(uniqueConstraints={@UniqueConstraint(name="unique_name_in_layout_idx", columns={"name", "layout_id"})}))
 */
class ThemeLayoutPlaceholder extends Entity
{

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;

	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $container;
	
	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $defaultSetName;
	
	/**
	 * @ManyToOne(targetEntity="ThemeLayout", inversedBy="placeholders")
	 * @JoinColumn(name="layout_id", referencedColumnName="id")
	 * @var ThemeLayout
	 */
	protected $layout;
	
	/**
	 * @ManyToOne(targetEntity="ThemeLayoutPlaceholderGroup", inversedBy="placeholders")
	 * @var ThemeLayoutPlaceHolderGroup
	 */
	protected $group;
	
	
	/**
	 * 
	 */
	public function __construct($name = null)
	{
		parent::__construct();
		$this->name = $name;
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
	 * @return ThemeLayout
	 */
	public function getLayout()
	{
		return $this->layout;
	}

	/**
	 * @param ThemeLayout $layout 
	 */
	public function setLayout(ThemeLayout $layout = null)
	{
		$this->layout = $layout;
	}
	
	/**
	 *
	 */
	public function getGroup()
	{
		return $this->group;
	}
	
	/**
	 *
	 */
	public function setGroup(ThemeLayoutPlaceholderGroup $group)
	{
		$this->group = $group;
	}
	
	/**
	 * 
	 */
	public function resetGroup()
	{
		$this->group = null;
	}

}
