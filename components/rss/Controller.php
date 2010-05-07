<?php

namespace Project\Rss;

use Supra\Event\Registry as EventRegistry;
use Supra\Log\Logger as Log;

/**
 * RSS controller
 */
class Controller extends \Supra\Controller\Simple
{
	protected function indexAction()
	{
		$instanceListenerFunction = function() {
			Log::info('RSS controller instance listener succeeded');
		};
		EventRegistry::listen($this, 'test', $instanceListenerFunction);
		
		// test calling listener by instance and by classname
		EventRegistry::fire($this, 'index');
		EventRegistry::fire(__CLASS__, 'index');
		$this->getResponse()->output('INDEX');
		EventRegistry::fire($this, 'test');

	}
	protected function fooBarAction()
	{
		$this->getResponse()->output('foo/bar action');
	}
}