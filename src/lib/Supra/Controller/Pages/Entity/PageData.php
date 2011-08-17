<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Controller\Pages\Exception;

/**
 * PageData class
 * @Entity
 * @Table
 * @HasLifecycleCallbacks
 */
class PageData extends Abstraction\Data
{
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $path = null;

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
			throw new Exception\PagePathException("Root page cannot have path assigned");
		}
		
		// Check if not trying to set empty path to not root page
		if ( ! $page->isRoot() && $pathPart == '') {
			throw new Exception\PagePathException("Path cannot be empty");
		}
		
		$this->pathPart = $pathPart;
		
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
	 * @TODO: must be run on inserts manually for now
	 * @throws Exception\PagePathException on duplicates
	 * @PreUpdate
	 */
	public function generatePath()
	{
		$page = $this->getMaster();
		$pathPart = $this->pathPart;
		
		if ( ! $page->isRoot()) {
			
			// Leave path empty if path part is not set yet
			if ($pathPart == '') {
				return;
			}
			
			$parentPage = $page->getParent();
			
			$path = $pathPart;
			
			// Root page has no path
			if ( ! $parentPage->isRoot()) {
				
				$parentData = $parentPage->getData($this->locale);
				
				if (empty($parentData)) {
					throw new Exception\RuntimeException("Parent page #{$parentPage->getId()} does not have the data for the locale {$this->locale} required by page {$page->getId()}");
				}
				
				$path = $parentData->getPath() . '/' . $pathPart;
			}
			
			// Duplicate path validation
			$criteria = array(
				'locale' => $this->locale,
				'path' => $path
			);
			
			//TODO: getRepository usage again, should refactor
			$duplicate = $this->getRepository()
					->findOneBy($criteria);
			
			if ( ! is_null($duplicate) && ! $page->equals($duplicate)) {
				throw new Exception\DuplicatePagePathException("Page with path $path already exists");
			}
			
			$this->setPath($path);
		} else {
			$this->setPath('');
		}
	}

}