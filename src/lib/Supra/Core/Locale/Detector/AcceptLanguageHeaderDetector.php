<?php

namespace Supra\Locale\Detector;

use Supra\Request\RequestInterface;
use Supra\Response\ResponseInterface;
use Supra\Request\HttpRequest;

/**
 * Tries to detect by Accept-Language header
 */
class AcceptLanguageHeaderDetector extends DetectorAbstraction
{
	/**
	 * Detects the current locale
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 * @return string
	 */
	public function detect(RequestInterface $request, ResponseInterface $response)
	{
		if ( ! ($request instanceof HttpRequest)) {
			\Log::warn('Request must be instance of Http request object to use cookie locale detection');
			return;
		}

		/* @var $request HttpRequest */
		$headerValue = $request->getServerValue('HTTP_ACCEPT_LANGUAGE', null);
		$acceptsList = $this->parseAcceptRequestHeader($headerValue);

		$lm = \Supra\ObjectRepository\ObjectRepository::getLocaleManager($this);

		foreach ($acceptsList as $accepts) {
			$localeIdentifier = $accepts['language'];
			
			if ($lm->exists($localeIdentifier, false)) {
				return $localeIdentifier;
			}
		}
		
		return;
	}

	/**
	 * Parses the header Accept-Language
	 * @param string $headerValue
	 * @return array
	 */
	private function parseAcceptRequestHeader($headerValue)
	{
		$accepts = array();

		if ( ! empty($headerValue)) {
			$acceptList = explode(',', $headerValue);

			foreach ($acceptList as $accept) {
				$matches = null;
				$isValid = preg_match('/([a-z]+)(\-([a-z]+))?(\s*;\s*q=([0-9\.]+))?/i', $accept, $matches);

				if ($isValid) {

					// Fill missing indeces with nulls
					$matches = $matches + array_fill(0, 6, null);

					$language = $matches[1];
					$country = $matches[3];
					$qValue = $this->parseLanguageQuality($matches[5]);

					$accepts[] = array(
						'language' => $language,
						'country' => $country,
						'quality' => $qValue
					);
				}
			}

			usort($accepts, array($this, 'sortByQuality'));

			// Remove all "accept" headers with 0 quality
			foreach ($accepts as $key => $accept) {
				if ($accept['quality'] <= 0) {
					unset($accepts[$key]);
				}
			}
		}

		return $accepts;
	}

	/**
	 * Sorts locales by quality descending
	 * @return int
	 */
	private function sortByQuality(array $localeA, array $localeB)
	{
		$qualityA = $localeA['quality'];
		$qualityB = $localeB['quality'];

		if ($qualityA == $qualityB) {
			return 0;
		}

		if ($qualityA < $qualityB) {
			return 1;
		}

		if ($qualityA > $qualityB) {
			return -1;
		}
	}

	/**
	 * Converts quality to float
	 * @param string $quality
	 * @return float
	 */
	private function parseLanguageQuality($quality)
	{
		$float = null;

		if (is_null($quality)) {
			$float = 1;
		} elseif (is_numeric($quality)) {
			$float = (float) $quality;
		} else {
			$float = 0;
		}

		return $float;
	}

}
