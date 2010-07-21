<?php

namespace Supra\Controller\Pages\Entity;

/**
 * Page Place Holder
 * @Entity
 */
class PagePlaceHolder extends Abstraction\PlaceHolder
{

	/**
	 * Set page
	 * @param Page $page
	 */
	public function setPage(Page $page)
	{
		$this->setMaster($page);
	}

	/**
	 * Get page
	 * @return Page
	 */
	public function getPage()
	{
		return $this->getMaster();
	}

}
