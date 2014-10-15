<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\HttpFoundation\SupraJsonResponse;
use Supra\Package\Cms\Pages\Exception\ObjectLockedException;

class PagesTemplateController extends AbstractPagesController
{
	/**
	 * Called on page/template editing start.
	 *
	 * @return SupraJsonResponse
	 */
	public function lockAction()
	{
		return $this->lockPage();
	}

	/**
	 * Called on page/template editing end.
	 *
	 * @return SupraJsonResponse
	 */
	public function unlockAction()
	{
		try {
			$this->checkLock();

			$this->unlockPage();

		} catch (ObjectLockedException $e) {
			// @TODO: check why it were made to ignore errors if locked.
		}

		return new SupraJsonResponse();
	}
}