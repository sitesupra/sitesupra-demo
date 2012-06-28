<?php

namespace Supra\Locale\PreFilter;

use Supra\Controller;
use Supra\Controller\Exception;
use Supra\Request;
use Supra\Response;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Locale detection PreFilter
 */
class LocaleDetectorPreFilter extends Controller\ControllerAbstraction implements Controller\PreFilterInterface
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
			\Log::warn('Did not found current locale. Stopping all requsts. Hint: check locale.php configuration for (new LocaleManager())->setCurrent()');
			throw new Exception\StopRequestException();
		}
	}
}