<?php

namespace Supra\Package\Cms\Pages;

use Supra\Package\Cms\Pages\Request\PageRequest;
use Supra\Package\Cms\Controller\PageController;

class PageExecutionContext
{
	/**
	 * @var PageRequest
	 */
	public $request;

	/**
	 * @var PageController
	 */
	public $controller;

	/**
	 * @param PageRequest $request
	 * @param PageController $controller
	 */
	public function __construct(
			PageRequest $request,
			PageController $controller
	) {
		$this->request = $request;
		$this->controller = $controller;
	}
}