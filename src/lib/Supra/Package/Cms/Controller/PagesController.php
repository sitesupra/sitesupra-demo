<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\Controller\Controller;

class PagesController extends Controller
{
	protected $application = 'content-manager';

	public function indexAction()
	{
		return $this->renderResponse('index.html.twig');
	}

}
