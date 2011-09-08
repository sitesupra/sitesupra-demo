<?php

namespace Project\DistributedController\Index;

/**
 * Description of Index
 */
class IndexAction extends \Supra\Controller\SimpleController
{
	public function indexAction()
	{
		$this->response->output('This is an index action');
	}
	
	public function fooAction($username)
	{
		$this->response->output('FOO');
	}
}
