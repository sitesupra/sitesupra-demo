<?php

namespace Supra\Core\Locale\Detector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Request parameter locale detector
 */
class ParameterDetector implements DetectorInterface
{
	/**
	 * @var string
	 */
	private $parameterName;

	/**
	 * @param string|null $parameterName Parameter name used to pass the locale ID
	 */
	public function __construct($parameterName = 'locale')
	{
		$this->parameterName = $parameterName;
	}

	/**
	 * Detects the current locale
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return string
	 */
	public function detect(Request $request)
	{
		if ($request->isMethod('post')) {
			return $request->request->get($this->parameterName);
		}

		return $request->query->get($this->parameterName);
	}
}