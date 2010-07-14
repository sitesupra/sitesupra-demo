<?php

namespace Supra\Controller\Pages;

use Doctrine\Common\Collections\ArrayCollection,
		Doctrine\Common\Collections\Collection;

/**
 * Page controller page object
 * @Entity
 * @Table(name="page")
 */
class Page extends PageAbstraction
{
	/**
	 * Data class
	 * @var string
	 */
	static protected $dataClass = 'PageData';

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
		$this->isInstanceOf($page, __NAMESPACE__ . '\Page', __METHOD__);
		
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

	/**
	 * Get page template hierarchy starting with the root template
	 * @return Template[]
	 * @throws Exception
	 */
	public function getTemplates()
	{
		$template = $this->getTemplate();

		if (empty($template)) {
			//TODO: 404 page or specific error?
			throw new Exception("No template assigned to the page {$page->getId()}");
		}

		/* @var $templates Template[] */
		$templates = array();
		do {
			array_unshift($templates, $template);
			$template = $template->getParent();
		} while ( ! is_null($template));

		return $templates;
	}

}