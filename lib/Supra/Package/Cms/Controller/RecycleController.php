<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\HttpFoundation\SupraJsonResponse;

class RecycleController extends AbstractCmsController
{
	public function loadPagesAction()
	{
		return new SupraJsonResponse(array());
	}
}
