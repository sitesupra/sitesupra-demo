<?php

namespace Supra\Controller\Pages\Event;

/**
 */
class PageCmsEventArgs extends \Supra\Event\EventArgs
{
	/**
	 * @var \Supra\User\Entity\User
	 */
	public $user;
	
	/**
	 * @var \Supra\Controller\Pages\Entity\Abstraction\Localization
	 */
	public $localization;
}
