<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Controller\Pages\Exception;

/**
 * PageData class
 * @Entity
 * @Table(name="page_data")
 * @HasLifecycleCallbacks
 */
class PageData extends Abstraction\Data
{
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $path = '';

	/**
	 * @Column(type="string", name="path_part")
	 * @var string
	 */
	protected $pathPart = '';

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

		$page = $this->getMaster();

		if (empty($page)) {
			throw new Exception\RuntimeException('Page data page object must be set before setting path part');
		}

		// Check if path part is not added to the root page
		// TODO: maybe should make more elegant solution than level check?
		$level = $page->getLevel();

		if ($level == 0) {
			\Log::debug("Cannot set path for the root page");
			$this->setPath('');
			return;
		}
		
		$this->generatePath();
	}

	/**
	 * @return string
	 */
	public function getPathPart()
	{
		return $this->pathPart;
	}
	
	/**
	 * TODO: Not sure whether it can be useful, doesn't run on inserts...
	 * @PreUpdate
	 */
	public function generatePath()
	{
		$pathPart = urlencode($this->pathPart);

		$page = $this->getMaster();
		
		if ($page->hasParent()) {
			$parentPage = $page->getParent();

			$parentData = $parentPage->getData($this->getLocale());
			if (empty($parentData)) {
				throw new Exception\RuntimeException("Parent page #{$parentPage->getId()} does not have the data for the locale {$this->getLocale()} required by page {$page->getId()}");
			}
			$path = $parentData->getPath();

			$path .= '/' . $pathPart;

			$this->setPath($path);
		} else {
			$this->setPath(null);
		}
	}

}