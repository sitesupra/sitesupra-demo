<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\Controller\Controller;
use Supra\Core\HttpFoundation\SupraJsonResponse;

class CmsController extends Controller
{
	public function sessionCheckAction()
	{
		return new SupraJsonResponse();
	}
}
