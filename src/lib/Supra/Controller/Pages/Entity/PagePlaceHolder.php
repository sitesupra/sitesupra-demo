<?php

namespace Supra\Controller\Pages\Entity;

/**
 * Page Place Holder
 * @Entity
 * @Table(name="page_place_holder")
 */
class PagePlaceHolder extends Abstraction\PlaceHolder
{
	/**
	 * @ManyToOne(targetEntity="Page")
	 * @var Page
	 */
	protected $page;

	/**
	 * @OneToMany(targetEntity="PageBlock", mappedBy="placeHolder", cascade={"persist", "remove"})
	 * @var Collection
	 */
	protected $blocks;

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
	 * @param Abstraction\Page $master
	 */
	public function setMaster(Abstraction\Page $master)
	{
		$this->isInstanceOf($master, __NAMESPACE__ . '\Page', __METHOD__);
		$this->setPage($master);
	}

	/**
	 * Checks block object instance
	 * @param $block Abstraction\Block
	 * @throws Exception on failure
	 */
	protected function checkBlock(Abstraction\Block $block)
	{
		$this->isInstanceOf($block, __NAMESPACE__ . '\PageBlock', __METHOD__);
	}
}
