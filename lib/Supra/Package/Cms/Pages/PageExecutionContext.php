<?php

namespace Supra\Package\Cms\Pages;

use Supra\Package\Cms\Entity\Abstraction\Localization;
use Supra\Package\Cms\Pages\Request\PageRequest;
use Supra\Package\Cms\Pages\Response\PageResponse;
use Supra\Package\Cms\Controller\PageController;

class PageExecutionContext
{
	/**
	 * @var PageRequest
	 */
	public $request;

	/**
	 * @var PageResponse
	 */
	public $response;

	/**
	 * @var PageController
	 */
	public $controller;

	/**
	 * @var Localization
	 */
	public $localization;

	/**
	 * @param Localization $localization
	 * @param PageRequest $request
	 */
	public function __construct(
			Localization $localization,
			PageController $controller,
			PageRequest $request,
			PageResponse $response
	) {
		$this->localization = $localization;
		$this->controller = $controller;
		$this->request = $request;
		$this->response = $response;
	}
}