<?php

namespace Supra\Cms\MediaLibrary;

use Supra\Controller\SimpleController;

/**
 * Media library controller
 */
class MediaLibraryController extends SimpleController
{
	/**
	 * Main action
	 */
	public function indexAction()
	{
		$output = file_get_contents(__DIR__ . '/index.html');
		
		$this->getResponse()
				->output($output);
	}
}
