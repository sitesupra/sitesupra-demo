<?php

namespace Project\DistributedController\Index;

/**
 * Description of Index
 */
class IndexAction extends \Supra\Controller\SimpleController
{
	/**
	 * Overriden so PHP <= 5.3.2 doesn't treat indexAction() as a constructor
	 */
	public function __construct()
	{
		parent::__construct();
	}
	
	public function indexAction()
	{
		$this->response->output('This is an index action');
	}
	
	public function fooAction($username)
	{
		$this->response->output('FOO');
	}
}
