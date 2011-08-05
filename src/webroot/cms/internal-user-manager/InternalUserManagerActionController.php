<?php

namespace Supra\Cms\InternalUserManager;

use Supra\Controller\SimpleController;
use Supra\Response\JsonResponse;
use Supra\Request;

/**
 * Internal user manager action controller
 * @method JsonResponse getResponse()
 */
class InternalUserManagerActionController extends SimpleController
{
	/**
	 * @return JsonResponse
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		$response = new JsonResponse();
		
		return $response;
	}
}

