<?php

namespace Supra\Cms\ContentManager\root;

/**
 */
class RootAction extends \Supra\Controller\SimpleController
{
	public function indexAction()
	{
		//TODO: introduce some template engine
		$output = file_get_contents(__DIR__ . '/index.html');
		$this->response->output($output);
	}
}
