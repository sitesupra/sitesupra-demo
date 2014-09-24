<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\Controller\Controller;

class DashboardController extends Controller
{
	protected $application = 'cms_dashboard';

	public function indexAction()
	{
		return $this->renderResponse('index.html.twig');
	}

}
