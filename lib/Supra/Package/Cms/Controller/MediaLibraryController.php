<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\Controller\Controller;

class MediaLibraryController extends Controller
{
	protected $application = 'media-library';

	public function indexAction()
	{
		return $this->renderResponse('index.html.twig');
	}
}
