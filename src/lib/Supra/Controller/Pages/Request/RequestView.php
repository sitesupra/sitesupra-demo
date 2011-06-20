<?php

namespace Supra\Controller\Pages\Request;

use Supra\Request\Http;

/**
 * Page controller request object on view method
 */
class RequestView extends Request
{
	/**
	 * @param Http $request
	 */
	public function __construct(Http $request)
	{
		// Not nice but functional method to downcast the request object
		foreach ($request as $field => $value) {
			$this->$field = $value;
		}
	}
}
