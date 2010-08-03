<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Controller\Pages\Exception;

/**
 * PageData class
 * @Entity
 * @Table(name="page_data")
 */
class PageData extends Abstraction\Data
{
	/**
	 * @ManyToOne(targetEntity="Page", inversedBy="data", fetch="EAGER")
	 * @JoinColumn(name="page_id", referencedColumnName="id", nullable=false)
	 * @var Page
	 */
	protected $page;

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
	 * @param Page $page
	 */
	public function setPage(Page $page)
	{
		if ($this->writeOnce($this->page, $page)) {
			$page->setData($this);
		}
	}

	/**
	 * Set master object page
	 * @param Abstraction\Page $master
	 */
	public function setMaster(Abstraction\Page $master)
	{
		$this->matchDiscriminator($master);
		$this->setPage($master);
	}

	/**
	 * @return Page
	 */
	public function getPage()
	{
		return $this->page;
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

	/**
	 * Sets path part of the page
	 * @param string $pathPart
	 */
	public function setPathPart($pathPart)
	{

		$this->pathPart = $pathPart;

		$page = $this->getPage();

		if (empty($page)) {
			throw new Exception('Page data page object must be set before setting path part');
		}

		$parentPage = $page->getParent();

		if (is_null($parentPage)) {
			\Log::debug("Cannot set path for the root page");
			$this->setPath('');
			return;
		}

		$pathPart = \urlencode($pathPart);

		if ($pathPart == '') {
			throw new Exception('Path part cannot be empty');
		}

		$parentData = $parentPage->getData($this->getLocale());
		if (empty($parentData)) {
			throw new Exception("Parent page #{$parentPage->getId()} does not have the data for the locale {$this->getLocale()} required by page {$page->getId()}");
		}
		$path = $parentData->getPath();

		$path .= '/' . $pathPart;

		$this->setPath($path);
	}

}