<?php

namespace Supra\Cms\BannerManager\Root;

use Supra\Controller\SimpleController;

class RootAction extends SimpleController
{

	public function indexAction()
	{
		$output = file_get_contents(__DIR__ . '/index.html');

		$this->getResponse()
				->output($output);
	}

}

