<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Database;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity 
 * @Table(uniqueConstraints={@UniqueConstraint(name="unique_name_in_theme_idx", columns={"name", "theme_id"})}))
 */
class ThemeLayout extends Database\Entity
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
	 * @Column(type="string")
	 * @var string
	 */
	protected $filename;

	/**
	 * @ManyToOne(targetEntity="Theme", inversedBy="layouts")
	 * @JoinColumn(name="theme_id", referencedColumnName="id")
	 * @var Theme
	 */
	protected $theme;

	/**
	 * @OneToMany(targetEntity="ThemeLayoutPlaceholder", mappedBy="layout", cascade={"all"}, orphanRemoval=true, indexBy="name")
	 * @var ArrayCollection
	 */
	protected $placeholders;

	public function __construct()
	{
		parent::__construct();

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
	 * @return string
	 */
	public function getFilename()
	{
		return $this->filename;
	}

	/**
	 * @param string $filename 
	 */
	public function setFilename($filename)
	{
		$this->filename = $filename;
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
	 * @return array
	 */
	public function getPlaceholders()
	{
		return $this->placeholders;
	}

	/**
	 * @param ThemeLayoutPlaceholder $placeholder 
	 */
	public function addPlaceholder(ThemeLayoutPlaceholder $placeholder)
	{
		$placeholder->setLayout($this);

		$this->placeholders[$placeholder->getName()] = $placeholder;
	}

	/**
	 * @param ThemeLayoutPlaceholder $placeholder 
	 */
	public function removePlaceholder(ThemeLayoutPlaceholder $placeholder)
	{
		$placeholder->setLayout(null);

		$this->placeholders->removeElement($placeholder);
	}

}
