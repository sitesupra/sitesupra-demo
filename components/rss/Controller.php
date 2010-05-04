<?php

namespace Project\Rss;

/**
 * RSS controller
 */
class Controller extends \Supra\Controller\Simple
{
	protected function indexAction()
	{
		$this->getResponse()->output('INDEX');
	}
	protected function fooBarAction()
	{
		$this->getResponse()->output('foo/bar action');
	}
}