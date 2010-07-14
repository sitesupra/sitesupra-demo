<?php

namespace Supra\Controller\Pages;

/**
 * Page Place Holder
 * @Entity
 * @Table(name="page_place_holder")
 */
class PagePlaceHolder extends PlaceHolder
{
	/**
	 * @ManyToOne(targetEntity="Page")
	 * @var Page
	 */
	protected $page;

	/**
	 * Set page
	 * @param Page $page
	 */
	public function setPage(Page $page)
	{
		if ($this->writeOnce($this->page, $page)) {
			$this->page->addPlaceHolder($this);
		}
	}

	/**
	 * Get page
	 * @return Page
	 */
	public function getPage()
	{
		return $this->page;
	}

	/**
	 * Set master object
	 * @param PageAbstraction $master
	 */
	public function setMaster(PageAbstraction $master)
	{
		$this->isInstanceOf($master, __NAMESPACE__ . '\Page', __METHOD__);
		$this->setPage($master);
	}
}
