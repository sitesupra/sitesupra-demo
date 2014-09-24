<?php

namespace Supra\Cms\CheckSession;

use Supra\Cms\CmsAction;

class CheckSessionController extends CmsAction
{
	/**
	 * Default action when no action is provided
	 * @var string
	 */
	protected $defaultAction = 'index';

	/**
	 * Does nothing, just outputs true
	 * Main logic is located in AuthenticationController:
	 *   if session is timed out/broken/missing
	 *   this action just won't be executed
	 */
	public function indexAction()
	{
		$this->getResponse()
				->setResponseData(true);
	}

}
