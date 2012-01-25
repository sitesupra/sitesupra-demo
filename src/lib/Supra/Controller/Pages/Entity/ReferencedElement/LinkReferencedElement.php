<?php

namespace Supra\Controller\Pages\Entity\ReferencedElement;

use Supra\Controller\Pages\Entity\Abstraction\Localization;
use Supra\FileStorage\Entity\File;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Uri\Path;
use Supra\Controller\Pages\Entity\Abstraction\AbstractPage;
use Supra\Controller\Pages\Entity\GroupPage;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Uri\NullPath;
use Doctrine\ORM\NoResultException;

/**
 * @Entity
 */
class LinkReferencedElement extends ReferencedElementAbstract
{
	const TYPE_ID = 'link';
	
	const RESOURCE_PAGE = 'page';
	const RESOURCE_FILE = 'file';
	const RESOURCE_LINK = 'link';
	const RESOURCE_RELATIVE_PAGE = 'relative';
	
	const RELATIVE_LAST = 'last';
	const RELATIVE_FIRST = 'first';
	
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $resource;
	
	/**
	 * @Column(type="string", nullable="true")
	 * @var string
	 */
	protected $href;
	
	/**
	 * @Column(type="string", nullable="true")
	 * @var string
	 */
	protected $target;
	
	/**
	 * @Column(type="string", nullable="true")
	 * @var string
	 */
	protected $title;
	
	/**
	 * Page master ID to keep link data without existant real page.
	 * SQL naming for CMS usage, should be fixed (FIXME).
	 * @Column(type="supraId20", nullable="true")
	 * @var string
	 */
	protected $pageId;
	
	/**
	 * File ID to keep link data without existant real file.
	 * SQL naming for CMS usage, should be fixed (FIXME).
	 * @Column(type="supraId20", nullable="true")
	 * @var string
	 */
	protected $fileId;
	
	/**
	 * Internally cached page localization
	 * @var PageLocalization
	 */
	private $pageLocalization;
	
	/**
	 * @return string
	 */
	public function getResource()
	{
		return $this->resource;
	}

	/**
	 * @param string $resource
	 */
	public function setResource($resource)
	{
		$this->resource = $resource;
	}

	/**
	 * @return string
	 */
	public function getHref()
	{
		return $this->href;
	}

	/**
	 * @param string $resource
	 */
	public function setHref($href)
	{
		$this->href = $href;
	}
	
	/**
	 * @return string
	 */
	public function getTarget()
	{
		return $this->target;
	}

	/**
	 * @param string $target
	 */
	public function setTarget($target)
	{
		$this->target = $target;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $resource
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}
	
	/**
	 * Get element title
	 * @return string
	 */
	public function getElementTitle()
	{
		$title = null;
		
		switch ($this->resource) {
			
			case self::RESOURCE_PAGE:
				$pageData = $this->getPage();

				/* @var $pageData Localization */
				if ( ! is_null($pageData)) {
					$title = $pageData->getTitle();
				}
				break;
			
			case self::RESOURCE_FILE:
				$file = $this->getFile();

				if ($file instanceof File) {
					$localeId = ObjectRepository::getLocaleManager($this)
							->getCurrent()
							->getId();
					
					$title = $file->getTitle($localeId);
				}

				break;
				
			case self::RESOURCE_LINK:
				$title = $this->getHref();
				break;
			
			case self::RESOURCE_RELATIVE_PAGE:
				$href = $this->getHref();
				if ($href == self::RELATIVE_FIRST) {
					$title = 'First child';
				} else {
					$title = 'Last child';
				}
				break;
				
			default:
				$this->log()->warn("Unrecognized resource for supra html markup link tag, data: $this");
		}
		
		return $title;
	}
	
	/**
	 * @return string
	 */
	public function getPageId()
	{
		return $this->pageId;
	}

	/**
	 * @param string $pageId
	 */
	public function setPageId($pageId)
	{
		$this->pageLocalization = null;
		$this->pageId = $pageId;
	}

	/**
	 * @return string
	 */
	public function getFileId()
	{
		return $this->fileId;
	}

	/**
	 * @param string $fileId
	 */
	public function setFileId($fileId)
	{
		$this->fileId = $fileId;
	}

	/**
	 * Method to override the used page localization
	 * @param PageLocalization $pageLocalization 
	 */
	public function setPageLocalization(PageLocalization $pageLocalization)
	{
		$this->pageLocalization = $pageLocalization;
		$this->pageId = $pageLocalization->getMaster()->getId();
	}

	/**
	 * {@inheritdoc}
	 * @return array
	 */
	public function toArray()
	{
		$array = array(
			'type' => self::TYPE_ID,
			'resource' => $this->resource,
			'title' => $this->title,
			'target' => $this->target,
			'page_master_id' => $this->pageId,
			'file_id' => $this->fileId,
			'href' => $this->href,
		);
		
		return $array;
	}
	
	/**
	 * {@inheritdoc}
	 * @param array $array
	 */
	public function fillArray(array $array)
	{
		$this->resource = $array['resource'];
		$this->title = $array['title'];
		$this->target = $array['target'];
		$this->pageId = $array['page_master_id'];
		$this->fileId = $array['file_id'];
		$this->href = $array['href'];
		
		$this->pageLocalization = null;
	}
	
	/**
	 * @return Localization
	 */
	public function getPage()
	{
		if (empty($this->pageId)) {
			return;
		}
		
		if ( ! is_null($this->pageLocalization)) {
			return $this->pageLocalization;
		}
		
		$em = ObjectRepository::getEntityManager($this);
		
		$pageData = null;
		$localizationEntity = Localization::CN();
		$localeId = ObjectRepository::getLocaleManager($this)
				->getCurrent()
				->getId();
		
		$criteria = array(
			'master' => $this->pageId,
			'locale' => $localeId,
		);
		
		// Now master page ID is stored, still the old implementation is working
		$dql = "SELECT l FROM $localizationEntity l
				WHERE (l.master = :master AND l.locale= :locale) 
				OR l.id = :master";
		
		try {
			$pageData = $em->createQuery($dql)
					->setParameters($criteria)
					->getSingleResult();
		} catch (NoResultException $noResult) {
			
			// Special case for group page selection when no localization exists in database
			$master = $em->find(GroupPage::CN(), $this->pageId);
			
			if ($master instanceof GroupPage) {
				$pageData = $master->getLocalization($localeId);
			}
		}
		
		// Cache the result
		$this->pageLocalization = $pageData;
		
		return $pageData;
	}
	
	/**
	 * @return File
	 */
	public function getFile()
	{
		if (empty($this->fileId)) {
			return;
		}
		
		$fs = ObjectRepository::getFileStorage($this);
		$em = $fs->getDoctrineEntityManager();
		$file = $em->find(File::CN(), $this->fileId);
		
		return $file;
	}
	
	/**
	 * Generates full page URL with locale prefix
	 * @param Localization $pageLocalization
	 * @return string
	 */
	private function getPageFullPath(Localization $pageLocalization)
	{
		if ( ! $pageLocalization instanceof PageLocalization) {
			return null;
		}
		
		$path = $pageLocalization->getPath();
		$url = null;

		if ( ! is_null($path) && ! $path instanceof NullPath) {
			$url = $path->getPath(Path::FORMAT_BOTH_DELIMITERS);

			$localeId = $pageLocalization->getLocale();
			$url = '/' . $localeId . $url;
		}
		
		return $url;
	}
	
	/**
	 * Get URL of the link
	 * @return string
	 */
	public function getUrl()
	{
		$url = null;
		
		switch ($this->getResource()) {
			
			case self::RESOURCE_PAGE:
				$pageData = $this->getPage();

				if ( ! is_null($pageData)) {
					$url = $this->getPageFullPath($pageData);
				}
				break;
			
			case self::RESOURCE_FILE:
				$file = $this->getFile();

				if ($file instanceof File) {
					$fs = ObjectRepository::getFileStorage($this);
					$url = $fs->getWebPath($file);
				}

				break;
				
			case self::RESOURCE_LINK:
				$url = $this->getHref();
				break;
			
			case self::RESOURCE_RELATIVE_PAGE:
				$page = $this->getPage();
				
				if (is_null($page)) {
					$this->log()->warn("No page ID set or found for relative link #", $this->getId());
					throw new ResourceNotFoundException("Invalid redirect");
				}
				
				$pageChildren = $page->getPublicChildren();
				
				if ( ! $pageChildren->isEmpty()) {
					$type = $this->getHref();
					$relativeChild = null;

					if ($type == self::RELATIVE_FIRST) {
						$relativeChild = $pageChildren->first();
					} else {
						$relativeChild = $pageChildren->last();
					}

					$url = $this->getPageFullPath($relativeChild);
				} else {
					//throw new ResourceNotFoundException('Valid relative redirect child was not found');
					return null;
				}
				break;

			default:
				$this->log()->warn("Unrecognized resource for supra html markup link tag, data: $this");
		}
		
		return $url;
	}

}
