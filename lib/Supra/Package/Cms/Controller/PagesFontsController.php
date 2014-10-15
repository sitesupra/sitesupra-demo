<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\HttpFoundation\SupraJsonResponse;

class PagesFontsController extends AbstractPagesController
{
	/**
	 * @TODO: obtain the list.
	 *
	 * @return SupraJsonResponse
	 */
	public function googleFontsListAction()
	{
		return new SupraJsonResponse(array());
	}
}