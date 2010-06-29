<?php

namespace Supra\Controller\Pages;

/**
 * PageData class
 * @Entity
 * @Table(name="page_data")
 */
class PageData extends PageDataAbstraction
{
	/**
	 * @ManyToOne(targetEntity="Page", inversedBy="data")
	 * @var Page
	 */
	protected $page;

	/**
	 * @param Page $page
	 */
	public function setPage(Page $page)
	{
		$this->page = $page;
	}

	/**
	 * @return Page
	 */
	public function getPage()
	{
		return $this->page;
	}

}