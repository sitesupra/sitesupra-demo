<?php

namespace Supra\Controller\Pages\Entity;

use Doctrine\Common\Collections\ArrayCollection,
		Doctrine\Common\Collections\Collection,
		Supra\Controller\Pages\Exception;

/**
 * Page controller page object
 * @Entity
 * @Table(name="page")
 */
class Page extends Abstraction\Page
{
	/**
	 * Data class
	 * @var string
	 */
	static protected $dataClass = 'Supra\Controller\Pages\Entity\PageData';

	/**
	 * @OneToMany(targetEntity="PageData", mappedBy="page", cascade={"persist", "remove"})
	 * @var Collection
	 */
	protected $data;

	/**
	 * @ManyToOne(targetEntity="Template", cascade={"persist"})
	 * @JoinColumn(name="template_id", referencedColumnName="id", nullable=false)
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
	 * @param Abstraction\Page $page
	 */
	public function setParent(Abstraction\Page $page = null)
	{
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