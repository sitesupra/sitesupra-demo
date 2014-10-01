<?php

namespace Supra\Package\Cms\Entity;

use Supra\Uri\Path;

/**
 * @Entity
 */
class RedirectTargetPage extends Abstraction\RedirectTarget
{
	/**
	 * @ManyToOne(targetEntity="Supra\Package\Cms\Entity\Page")
	 * 
	 * @var Page
	 */
	protected $page;

	/**
	 * {@inheritDoc}
	 */
	public function getRedirectUrl()
	{
		$localeId = $this->getPageLocalization()
				->getId();
		
		$targetLocalization = $this->page->getLocalization($localeId);

		return $targetLocalization
				? $targetLocalization->getPath()->format(Path::FORMAT_BOTH_DELIMITERS)
				: null;
	}

	/**
	 * @return Page
	 */
	public function getTargetPage()
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
}