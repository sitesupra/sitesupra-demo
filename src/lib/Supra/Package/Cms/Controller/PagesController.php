<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class PagesController extends Controller
{
	public function indexAction()
	{
		return new Response('Pages app should be ported here');
	}

}
