<?php

namespace Supra\Controller\Pages;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Page controller page object
 * @Entity
 */
class Page extends PageAbstraction
{

	/**
	 * @OneToMany(targetEntity="PageData", mappedBy="page", cascade={"persist", "remove"})
	 * @var Collection
	 */
	protected $data;

	/**
	 * @ManyToOne(targetEntity="Template", cascade={"persist"})
	 * @var Template
	 */
	protected $template;

	/**
	 * @OneToMany(targetEntity="Page", mappedBy="parent")
	 * @var Collection
	 */
	protected $children;

	/**
     * @ManyToOne(targetEntity="Page", inversedBy="children")
     * @JoinColumn(name="parent_id", referencedColumnName="id")
	 * @var Page
     */
	protected $parent;

	/**
	 * Page place holders
	 * @OneToMany(targetEntity="PagePlaceHolder", mappedBy="page", cascade={"persist", "remove"})
	 * @var Collection
	 */
	protected $placeHolders;

	/**
	 * @Column(type="integer")
	 * @var int
	 */
	protected $depth = 1;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->pageData = new ArrayCollection();
	}

	/**
	 * Set page template
	 * @param Template $template
	 */
	public function setTemplate(Template $template)
	{
		$this->template = $template;
	}

	/**
	 * Get page template
	 * @return Template
	 */
	public function getTemplate()
	{
		return $this->template;
	}

	/**
	 * Sets page as parent of this page
	 * @param PageAbstraction $page
	 */
	public function setParent(PageAbstraction $page = null)
	{
		if ( ! ($page instanceof Page)) {
			throw new Exception("Page parent can be only instance of Page class");
		}
		parent::setParent($page);

		// Change full path for all data items
		$pageDatas = $this->getDataCollection();
		foreach ($pageDatas as $pageData) {
			/* @var $pageData PageData */
			$pageData->setPathPart($pageData->getPathPart());
		}

		$this->setDepth($page->depth + 1);
	}

	/**
	 * Set page depth
	 * @param int $depth
	 */
	protected function setDepth($depth)
	{
		$this->depth = $depth;
	}

}