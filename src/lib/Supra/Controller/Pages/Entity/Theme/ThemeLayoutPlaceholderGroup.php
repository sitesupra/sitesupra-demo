<?php

namespace Supra\Controller\Pages\Entity\Theme;

use Doctrine\Common\Collections\ArrayCollection;


/**
 * @Entity 
 * ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class ThemeLayoutPlaceholderGroup extends \Supra\Database\Entity
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
	 * @ManyToOne(targetEntity="ThemeLayout", inversedBy="placeholderGroup")
	 * @JoinColumn(name="layout_id", referencedColumnName="id")
	 * @var ThemeLayout
	 */
	protected $layout;
	
	/**
	 * @OneToMany(targetEntity="ThemeLayoutPlaceholder", mappedBy="group", cascade={"all"}, orphanRemoval=true, indexBy="name")
	 * @var ArrayCollection
	 */
	protected $placeholders;

	
	/**
	 * @param string $name
	 * @param string $title
	 */
	public function __construct($name)
	{
		parent::__construct();
		
		$this->name = $name;
		$this->placeholders = new ArrayCollection();
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
	 * @return ArrayCollection
	 */
	public function getPlaceholders()
	{
		return $this->placeholders;
	}
	
	/**
	 * @param \Supra\Controller\Pages\Entity\Theme\ThemeLayoutPlaceholder $placeholder
	 */
	public function addPlaceholder(ThemeLayoutPlaceholder $placeholder)
	{
		$placeholder->setGroup($this);
		$this->placeholders->set($placeholder->getName(), $placeholder);
	}
	
	/**
	 * @param \Supra\Controller\Pages\Entity\Theme\ThemeLayoutPlaceholder $placeholder
	 */
	public function removePlaceholder(ThemeLayoutPlaceholder $placeholder)
	{
		$placeholder->resetGroup();
		$this->placeholders->removeElement($placeholder);
	}
	
	/**
	 * 
	 */
	public function getDefaultLayout()
	{
		return $this->layout->getTheme()
				->getPlaceholderGroupLayouts()->first();
	}
}
