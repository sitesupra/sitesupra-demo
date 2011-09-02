<?php

namespace Project\Authentication;

use Supra\Controller;

/**
 * DummyController
 *
 * @author Dmitry Polovka <dmitry.polovka@videinfra.com>
 */
class DummyController extends Controller\ControllerAbstraction implements Controller\PreFilterInterface
{

	public function __construct()
	{
		$filename = '/home/dmitryp/dummy.log';
		$somecontent = time();
		
		$handle = fopen($filename, 'a');
		
		fwrite($handle, $somecontent);
		fclose($handle);
	}
	
	public function execute()
	{
		1+1;
		
//		$this->response->output('xxx');
//		
//		throw new Controller\Exception\StopRequestException();
	}


}