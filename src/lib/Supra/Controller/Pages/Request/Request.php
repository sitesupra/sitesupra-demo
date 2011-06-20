<?php

namespace Supra\Controller\Pages\Request;

use Supra\Request\Http;

/**
 * Page controller request
 */
abstract class Request extends Http
{
	/**
	 * @return \Supra\Controller\Pages\Entity\Abstraction\Page
	 */
	public function getRequestedPage()
	{
		//TODO: implement
	}
}
