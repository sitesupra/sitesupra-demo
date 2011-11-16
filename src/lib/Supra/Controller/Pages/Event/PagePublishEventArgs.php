<?php

namespace Supra\Controller\Pages\Event;

use Supra\Event\EventArgs;
use Supra\User\Entity\User;
use Supra\Controller\Pages\Entity\Abstraction\Localization;

class PagePublishEventArgs extends EventArgs
{
	/**
	 * @var User
	 */
	public $user;

	/**
	 * @var Localization
	 */
	public $localization;
}
