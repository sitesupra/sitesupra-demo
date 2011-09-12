<?php

namespace Project\Locale;

use Supra\Controller;
use Supra\Controller\Exception;
use Supra\Request;
use Supra\Response;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Locale detection PreFilter
 */
class LocalePreFilterController extends Controller\ControllerAbstraction implements Controller\PreFilterInterface
{
	/**
	 * Main method
	 */
	public function execute()
	{
		$localeManager = ObjectRepository::getLocaleManager($this);

		try {
			$localeManager->detect($this->request, $this->response);
		} catch (\Exception $e) {
			// ignore
		}
		
		$current = $localeManager->getCurrent();
		if (empty($current)) {
			throw new Exception\StopRequestException();
		}
	}
}