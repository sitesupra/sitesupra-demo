<?php

namespace Supra\Controller\Pages;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Page controller page object
 * @Entity
 */
class Page
{
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 * @var integer
	 */
	protected $id = null;

	/**
	 * @OneToOne(targetEntity="PageData")
	 * @var PageData
	 */
	protected $pageData;

	/**
	 * @OneToMany(targetEntity="Page", mappedBy="parent")
	 */
	protected $children;

	/**
     * @ManyToOne(targetEntity="Page", inversedBy="children")
     * @JoinColumn(name="parent_id", referencedColumnName="id")
     */
	protected $parent;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->children = new ArrayCollection();
	}

	/**
	 * Get page id
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Get parent page
	 * @return Page
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * Set parent page
	 * @var Page $parent
	 */
	public function setParent(Page $parent)
	{
		if ( ! empty($this->parent)) {
			$this->parent->getChildren()->remove($this);
		}
		$this->parent = $parent;
		$parent->getChildren()->add($this);
	}

	/**
	 * Get children pages
	 * @return ArrayCollection
	 */
	public function getChildren()
	{
		return $this->children;
	}

}