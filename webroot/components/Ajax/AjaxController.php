<?php

namespace Project\Ajax;

use Supra\Controller\DistributedController;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\Response\JsonResponse;

class AjaxController extends DistributedController
{
	public function execute()
	{
		$actionList = $this->getRequest()
				->getActions();
		
		// Just to skip searching for indexAction
		if (empty($actionList)) {
			throw new ResourceNotFoundException;
		}
		
		// FIXME: temporary solution
		try {
			parent::execute();
		} catch (\Exception $e) {
			$error = $e->getMessage();
			
			$output = array(
				'status' => 0,
				'error_message' => $error,
			);
			
			$this->getResponse()
					->output(json_encode($output));
		}
	}

}
