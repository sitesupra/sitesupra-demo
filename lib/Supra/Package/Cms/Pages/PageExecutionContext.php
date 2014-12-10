<?php

namespace Supra\Package\Cms\Pages;

use Supra\Package\Cms\Entity\Abstraction\Localization;
use Supra\Package\Cms\Pages\Request\PageRequest;

class PageExecutionContext
{
	/**
	 * @var PageRequest
	 */
	public $request;

	/**
	 * @var Localization
	 */
	public $localization;

	/**
	 * @param Localization $localization
	 * @param PageRequest $request
	 */
	public function __construct(Localization $localization, PageRequest $request)
	{
		$this->localization = $localization;
		$this->request = $request;
	}
}