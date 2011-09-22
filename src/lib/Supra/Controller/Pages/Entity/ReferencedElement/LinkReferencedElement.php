<?php

namespace Supra\Controller\Pages\Entity\ReferencedElement;

use Supra\Controller\Pages\Entity\Page;
use Supra\FileStorage\Entity\File;

/**
 * @Entity
 */
class LinkReferencedElement extends ReferencedElementAbstract
{
	protected $type = 'link';
	
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
	protected $title;
	
	/**
	 * Page ID to keep link data without existant real page.
	 * SQL naming for CMS usage, should be fixed (FIXME).
	 * @Column(type="string", nullable="true")
	 * @var string
	 */
	protected $page_id;
	
	/**
	 * File ID to keep link data without existant real file.
	 * SQL naming for CMS usage, should be fixed (FIXME).
	 * @Column(type="string", nullable="true")
	 * @var string
	 */
	protected $file_id;
	
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
	 * @return string
	 */
	public function getPageId()
	{
		return $this->page_id;
	}

	/**
	 * @param string $pageId
	 */
	public function setPageId($pageId)
	{
		$this->page_id = $pageId;
	}

	/**
	 * @return string
	 */
	public function getFileId()
	{
		return $this->file_id;
	}

	/**
	 * @param string $fileId
	 */
	public function setFileId($fileId)
	{
		$this->file_id = $fileId;
	}

}
