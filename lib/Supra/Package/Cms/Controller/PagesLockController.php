<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\HttpFoundation\SupraJsonResponse;

class PagesLockController extends AbstractPagesController
{
	/**
	 * Called on page editing start.
	 *
	 * @return SupraJsonResponse
	 */
	public function lockAction()
	{
		return $this->lockPage();
	}

	/**
	 * Called on page editing end.
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