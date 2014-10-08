<?php

namespace Supra\Controller\Pages;

/**
 * Created when block controller raises exception on initialization
 */
class NotInitializedBlockController extends BlockController
{
	public $exception;
	
	public function doExecute()
	{
		throw new Exception\RuntimeException("Could not initialize block controller", null, $this->exception);
	}
}
