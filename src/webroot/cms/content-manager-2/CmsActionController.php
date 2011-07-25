<?php

namespace Supra\Cms\ContentManager;

use Supra\Controller\SimpleController;
use Supra\Response\JsonResponse;
use Supra\Request;

/**
 * Description of CmsActionController
 * @method JsonResponse getResponse()
 */
class CmsActionController extends SimpleController
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
