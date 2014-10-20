<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class InternalUserManagerController extends Controller
{
	protected $application = 'internal-user-manager';

	public function indexAction(Request $request)
	{
		return $this->renderResponse('index.html.twig');
	}
}
