<?php

namespace Supra\Controller\Pages\Entity\ReferencedElement;

use Supra\Controller\Pages\Entity\Page;
use Supra\FileStorage\Entity\File;

/**
 * @Entity
 */
class LinkReferencedElement extends ReferencedElementAbstract
{
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
	 * @ManyToOne(targetEntity="Supra\Controller\Pages\Entity\Page")
	 * @var Page
	 */
	protected $page;
	
	/**
	 * @ManyToOne(targetEntity="Supra\FileStorage\Entity\File")
	 * @var File
	 */
	protected $file;
	
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
	 * @return Page
	 */
	public function getPage()
	{
		return $this->page;
	}

	/**
	 * @param Page $page
	 */
	public function setPage(Page $page)
	{
		$this->page = $page;
	}

	/**
	 * @return File
	 */
	public function getFile()
	{
		return $this->file;
	}

	/**
	 * @param File $file
	 */
	public function setFile(File $file)
	{
		$this->file = $file;
	}

}
