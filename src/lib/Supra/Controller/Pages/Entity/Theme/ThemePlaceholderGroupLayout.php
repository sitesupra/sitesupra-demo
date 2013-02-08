<?php

namespace Supra\Controller\Pages\Entity\Theme;


/**
 * @Entity
 * @ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class ThemePlaceholderGroupLayout extends \Supra\Database\Entity
{
	/**
	 * @Column(type="string")
	 * 
	 * @var string
	 */
	protected $name;
	
	/**
	 * @Column(type="string")
	 * 
	 * @var string
	 */
	protected $title;
	
	/**
	 * @Column(type="string")
	 * 
	 * @var string
	 */
	protected $fileName;
	
	/**
	 * @Column(type="text", nullable=true)
	 * 
	 * @var string
	 */
	protected $iconHtml;
		
	/**
	 * @ManyToOne(targetEntity="Theme", inversedBy="placeholderGroupLayouts")
	 * @JoinColumn(name="theme_id", referencedColumnName="id")
	 * @var Theme
	 */
	protected $theme;

	
	/**
	 * @param string $name
	 * @param string $fileName
	 */
	public function __construct($name)
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
	 * @return string
	 */
	public function getFileName()
	{
		return $this->fileName;
	}
	
	/**
	 * @param string $fileName
	 */
	public function setFileName($fileName)
	{
		$this->fileName = $fileName;
	}
	
	public function getIconHtml()
	{
		return $this->iconHtml;
	}
	
	/**
	 * @param string $html
	 */
	public function setIconHtml($html)
	{
		$this->iconHtml = $html;
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
