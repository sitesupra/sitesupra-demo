<?php

namespace Supra\Controller\Pages\Event;

use Supra\Event\EventArgs;

class PostPrepareContentEventArgs extends EventArgs
{
	
	/**
	 * @var PageRequest
	 */
	public $request;
	/**
	 * @var HttpResponse
	 */
	public $response;

}
