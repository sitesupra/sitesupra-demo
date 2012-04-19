<?php

namespace Supra\Controller\Pages\Event;

use Supra\Event\EventArgs;
use Supra\User\Entity\User;
use Supra\Controller\Pages\Entity\Abstraction\Localization;

/**
 * Raised on page events
 */
class CmsPageEventArgs extends EventArgs
{
	const postPagePublish = 'postPagePublish';
	const postPageDelete = 'postPageDelete';
	const postPageChange = 'postPageChange';
	
	/**
	 * @var User
	 */
	public $user;
	
	/**
	 * @var Localization
	 */
	public $localization;
}
