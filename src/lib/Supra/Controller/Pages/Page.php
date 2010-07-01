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
	 * @Column(type="string", unique=true)
	 * @var string
	 */
	protected $path = '';

	/**
	 * @Column(type="string", name="path_part")
	 * @var string
	 */
	protected $pathPart = '';

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
	 * Set page path
	 * @param string $path
	 */
	protected function setPath($path)
	{
		$path = trim($path, '/');
		$this->path = $path;
	}

	/**
	 * Get page path
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	public function setParent(PageAbstraction $page = null)
	{
		if ( ! ($page instanceof Page)) {
			throw new Exception("Page parent can be only instance of Page class");
		}
		parent::setParent($page);

		$this->setPathPart($this->pathPart);
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
	 * Sets path part of the page
	 * @param string $pathPart
	 */
	public function setPathPart($pathPart)
	{

		$this->pathPart = $pathPart;

		if (is_null($this->parent)) {
			\Log::debug("Cannot set path for the root page");
			$this->setPath('');
			return;
		}

		$pathPart = \urlencode($pathPart);

		if ($pathPart == '') {
			throw new Exception('Path part cannot be empty');
		}

		$path = $this->getParent()->getPath();

		$path .= '/' . $pathPart;

		$this->setPath($path);
	}

}