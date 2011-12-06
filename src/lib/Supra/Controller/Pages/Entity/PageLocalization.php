<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Controller\Pages\Exception;
use DateTime;
use Supra\Uri\Path;
use Supra\Controller\Pages\Entity\Page;
use Supra\Search\IndexedDocument;
use Supra\Controller\Pages\Request\PageRequestView;

/**
 * PageLocalization class
 * @Entity
 * @method PageLocalization getParent()
 * @method Page getMaster()
 */
class PageLocalization extends Abstraction\Localization
{
	/**
	 * {@inheritdoc}
	 */
	const DISCRIMINATOR = self::PAGE_DISCR;

	/**
	 * @ManyToOne(targetEntity="Template", fetch="EAGER")
	 * @JoinColumn(name="template_id", referencedColumnName="id", nullable=true)
	 * @History:SkipForeignKey(type="sha1")
	 * @Trash:SkipForeignKey(type="sha1")
	 * @var Template
	 */
	protected $template;

	/**
	 * Limitation because of MySQL unique constraint 1k byte limit
	 * @Column(type="path", length="255", nullable="true")
	 * @var Path
	 */
	protected $path = null;

	/**
	 * Used when current page has no path (e.g. news application)
	 * @Column(type="path", length="255")
	 * @var Path
	 */
	protected $parentPath = null;

	/**
	 * @Column(type="string", name="path_part")
	 * @var string
	 */
	protected $pathPart = '';

	/**
	 * @Column(type="string", name="meta_description")
	 * @var string
	 */
	protected $metaDescription = '';

	/**
	 * @Column(type="string", name="meta_keywords")
	 * @var string
	 */
	protected $metaKeywords = '';

	/**
	 * @Column(type="boolean")
	 * @var boolean
	 */
	protected $active = true;

	/**
	 * @Column(type="datetime", nullable=true, name="schedule_time")
	 * @var DateTime
	 */
	protected $scheduleTime;

	/**
	 * NB! Eager load is because "publish" action includes Doctrine merge action 
	 * which will fail if object isn't initialized.
	 * @ManyToOne(targetEntity="Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement", cascade={"all"}, fetch="EAGER")
	 * @var ReferencedElement\LinkReferencedElement
	 */
	protected $redirect;

	/**
	 * @Column(type="datetime")
	 * @var DateTime
	 */
	protected $creationTime;

	/**
	 * Automatically set, required because of DQL Group By limitations reported as improvement suggestion in DDC-1236
	 * @Column(type="smallint")
	 * @var int
	 */
	protected $creationYear;

	/**
	 * See $creationYear doc
	 * @Column(type="smallint")
	 * @var int
	 */
	protected $creationMonth;

	/**
	 * Used to reset the creation time on first publish or creation time set
	 * @Column(type="boolean")
	 * @var boolean
	 */
	protected $publishTimeSet = false;

	/**
	 * Additionally set creation time
	 * @param string $locale
	 */
	public function __construct($locale)
	{
		parent::__construct($locale);

		$this->setCreationTime();
		$this->publishTimeSet = false;
	}

	/**
	 * @return Page
	 */
	public function getPage()
	{
		return $this->getMaster();
	}

	/**
	 * @param Page $page
	 */
	public function setPage(Page $page)
	{
		$this->setMaster($page);
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
	 * Get page and it's template hierarchy starting with the root template
	 * @return PageSet
	 * @throws Exception\RuntimeException
	 */
	public function getTemplateHierarchy()
	{
		$template = $this->getTemplate();
		$page = $this->getPage();

		if (empty($template)) {
			//TODO: 404 page or specific error?
			throw new Exception\RuntimeException("No template assigned to the page {$page->getId()}");
		}

		$pageSet = $template->getTemplateHierarchy();
		$pageSet[] = $page;

		return $pageSet;
	}

	/**
	 * Set page path
	 * Should be called from the PagePathGenerator only!
	 * @param Path $path
	 */
	public function setPath(Path $path = null)
	{
		$this->path = $path;
	}

	/**
	 * Get page path
	 * @return Path
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * Sets path part of the page
	 * @param string $pathPart
	 * @TODO: added exception for experimental purposes
	 * @throws Exception\PagePathException if trying to set path for the root page
	 */
	public function setPathPart($pathPart)
	{
		// Remove all special characters
		$pathPart = preg_replace('!\?/\\\\#!', '', $pathPart);
		$pathPart = trim($pathPart);
		$page = $this->getMaster();

		if (empty($page)) {
			throw new Exception\RuntimeException('Page data page object must be set before setting path part');
		}

		// Check if path part is not added to the root page
		if ($page->isRoot() && $pathPart != '') {
			throw new Exception\PagePathException("Root page cannot have path assigned", $this);
		}

		// Now with news application it's possible...
		// FIXME: maybe should allow for applications only?
//		// Check if not trying to set empty path to not root page
//		if ( ! $page->isRoot() && $pathPart == '') {
//			throw new Exception\PagePathException("Path cannot be empty", $this);
//		}

		$this->pathPart = $pathPart;
	}

	/**
	 * @return string
	 */
	public function getPathPart()
	{
		return $this->pathPart;
	}

	/**
	 * @return Path
	 */
	public function getParentPath()
	{
		return $this->parentPath;
	}

	/**
	 * @param Path $parentPath
	 */
	public function setParentPath(Path $parentPath)
	{
		$this->parentPath = $parentPath;
	}

	/**
	 * @return string
	 */
	public function getMetaDescription()
	{
		return $this->metaDescription;
	}

	/**
	 * @param string $metaDescription
	 */
	public function setMetaDescription($metaDescription)
	{
		$this->metaDescription = $metaDescription;
	}

	/**
	 * @return string
	 */
	public function getMetaKeywords()
	{
		return $this->metaKeywords;
	}

	/**
	 * @param string $metaKeywords
	 */
	public function setMetaKeywords($metaKeywords)
	{
		$this->metaKeywords = $metaKeywords;
	}

	/**
	 * @return boolean
	 */
	public function isActive()
	{
		return $this->active;
	}

	/**
	 * @param boolean $active
	 */
	public function setActive($active)
	{
		$this->active = $active;
	}

	/**
	 * @return DateTime
	 */
	public function getScheduleTime()
	{
		return $this->scheduleTime;
	}

	/**
	 * @param DateTime $scheduleTime
	 */
	public function setScheduleTime(DateTime $scheduleTime)
	{
		$this->scheduleTime = $scheduleTime;
	}

	/**
	 * Unsets the schedule
	 */
	public function unsetScheduleTime()
	{
		$this->scheduleTime = null;
	}

	/**
	 * @return ReferencedElement\LinkReferencedElement
	 */
	public function getRedirect()
	{
		return $this->redirect;
	}

	/**
	 * @param ReferencedElement\LinkReferencedElement $redirect
	 */
	public function setRedirect(ReferencedElement\LinkReferencedElement $redirect = null)
	{
		$this->redirect = $redirect;
	}

	/**
	 * @return DateTime
	 */
	public function getCreationTime()
	{
		return $this->creationTime;
	}

	/**
	 * Sets creation time
	 * @param DateTime $creationTime
	 */
	public function setCreationTime(DateTime $creationTime = null)
	{
		if (is_null($creationTime)) {
			$creationTime = new DateTime();
		}

		$this->creationTime = $creationTime;
		$this->creationYear = $creationTime->format('Y');
		$this->creationMonth = $creationTime->format('n');
		$this->publishTimeSet = true;
	}

	/**
	 * Return if the creation time is already set for publishing
	 * @return boolean
	 */
	public function isPublishTimeSet()
	{
		return $this->publishTimeSet;
	}

	/**
	 * @return array
	 */
	protected function getAuthizationAncestorsDirect()
	{
		// This is overriden because page localizations themselves are not nested set element, so
		// we take master page, fetch all of its ancestors and then fetch page localizations from those.
		$ancestors = array();

		$master = $this->getMaster();
		$masterAncestors = $master->getAncestors();

		foreach ($masterAncestors as $masterAncestor) {
			/* @var $masterAncestor Page */

			$ancestors[] = $masterAncestor;

			$ancestorLocalization = $masterAncestor->getLocalization($this->locale);

			if ( ! empty($ancestorLocalization)) {
				$ancestors[] = $ancestorLocalization;
			}
		}

		return $ancestors;
	}
	
	public function __clone ()
	{
		parent::__clone();
		
		$this->path = null;
	}

}
