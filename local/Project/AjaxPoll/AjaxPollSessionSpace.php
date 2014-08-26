<?php

namespace Project\AjaxPoll;

use Supra\Session\SessionNamespace;

/**
 * Session space
 */
class AjaxPollSessionSpace extends SessionNamespace
{
	const CN = __CLASS__;
	
	/**
	 * @var boolean
	 */
	public $voted = false;
}
