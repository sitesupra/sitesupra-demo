<?php

namespace Supra\Controller\Pages\Entity\Theme;

use Supra\Database;

/**
 * @Entity 
 * ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * Table(uniqueConstraints={@UniqueConstraint(name="unique_name_in_layout_idx", columns={"name", "layout_id"})}))
 */
class ThemePlaceholderSet extends Database\Entity
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
	protected $layoutFilename;
	
	/**
	 * @ManyToOne(targetEntity="Theme", inversedBy="placeholderSets")
	 * @JoinColumn(name="theme_id", referencedColumnName="id")
	 * @var Theme
	 */
	protected $theme;

	public function __construct($name, $layout)
	{
		parent::__construct();
		
		$this->name = $name;
		$this->layoutFilename = $layout;
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
	
	public function getContainer()
	{
		return $this->container;
	}
	
	public function getLayoutFilename()
	{
		return $this->layoutFilename;
	}
	
	public function setLayoutFilename($layout)
	{
		$this->layoutFilename = $layout;
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

}
