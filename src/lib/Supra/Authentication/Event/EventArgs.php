<?php

namespace Supra\Authentication\Event;

use Supra\Request\HttpRequest;

/**
 * EventArgs for authentication events
 */
class EventArgs extends \Supra\Event\EventArgs
{
	const preAuthenticate = 'preAuthenticate';
	
	const onAuthenticationFailure = 'onAuthenticationFailure';
	
	const onAuthenticationSuccess = 'onAuthenticationSuccess';
	
	/**
	 * @var HttpRequest
	 */
	public $request;
}
