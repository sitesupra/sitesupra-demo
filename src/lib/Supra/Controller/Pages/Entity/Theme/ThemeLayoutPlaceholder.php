<?php

namespace Supra\Controller\Pages\Entity\Theme;

use Supra\Database;

/**
 * @Entity 
 * @ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * @Table(uniqueConstraints={@UniqueConstraint(name="unique_name_in_layout_idx", columns={"name", "layout_id"})}))
 */
class ThemeLayoutPlaceholder extends Database\Entity
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
	 * 
	 */
	public function __construct($containerName = null)
	{
		parent::__construct();
		
		$this->container = $containerName;
	}
	
	public function getContainer()
	{
		return $this->container;
	}
	
	public function setContainer($container)
	{
		$this->container = $container;
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
	 * @return string
	 */
	public function getDefaultSetName()
	{
		return $this->defaultSetName;
	}
	
	public function setDefaultSetName($defaultSetName)
	{
		$this->defaultSetName = $defaultSetName;
	}

}
