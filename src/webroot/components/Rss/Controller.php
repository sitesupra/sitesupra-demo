<?php

namespace Project\Rss;

use Supra\Event\Registry as EventRegistry,
		Supra\Log\Log,
		Supra\Controller\SimpleController;

/**
 * RSS controller
 */
class Controller extends SimpleController
{
	/**
	 * Default action
	 */
	protected function indexAction()
	{
		$instanceListenerFunction = function() {
			Log::info('RSS controller instance listener succeeded');
		};
		
		$eventManager = \Supra\ObjectRepository\ObjectRepository::getEventManager($this);
		
		$eventManager->listen('test', $instanceListenerFunction);
		
		// test calling listener
		$eventManager->fire('index');
		$this->getResponse()->output('Hello world!');
		$eventManager->fire('test');
	}

	/**
	 * Action for URL foo/bar
	 */
	protected function fooBarAction()
	{
		$this->getResponse()->output('foo/bar action');
	}
}