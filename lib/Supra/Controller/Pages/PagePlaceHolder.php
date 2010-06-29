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
		$this->page = $page;
	}

	/**
	 * Get page
	 * @return Page
	 */
	public function getPage()
	{
		return $this->page;
	}
}
