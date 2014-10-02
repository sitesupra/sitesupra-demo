<?php

namespace Supra\Core\Locale;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Supra\Core\Locale\Exception;

/**
 * Locale Manager
 */
class LocaleManager
{
	/**
	 * Locales defined
	 * 
	 * @var array
	 */
	protected $locales = array();

	/**
	 * @var Storage\StorageInterface[]
	 */
	protected $storages = array();

	/**
	 * Detectors defined
	 * @var Detector\DetectorInterface[]
	 */
	protected $detectors = array();

	/**
	 * Current locale identifier
	 * @var LocaleInterface
	 */
	protected $current;

	/**
	 * Weither to process inactive locales, or not
	 * @var boolean
	 */
	protected $processInactive = false;

	public function getLocaleArray()
	{
		$locales = $this->getLocales();

		$jsLocales = array();

		foreach ($locales as $locale) {

			$country = $locale->getCountry();

			if ( ! isset($jsLocales[$country])) {
				$jsLocales[$country] = array(
					'title' => $country,
					'languages' => array()
				);
			}

			$jsLocales[$country]['languages'][] = array(
				'id' => $locale->getId(),
				'title' => $locale->getTitle(),
				'flag' => $locale->getProperty('flag')
			);
		}

		$jsLocales = array_values($jsLocales);

		return $jsLocales;
	}

	/**
	 * Add locale data
	 * @param LocaleInterface $locale
	 * @throws Exception if Locale's ID is empty
	 */
	public function add(LocaleInterface $locale)
	{
		$id = $locale->getId();
		
		if (empty($id)) {
			throw new Exception\RuntimeException('Locale ID is not defined.');
		}
		
		$this->locales[$id] = $locale;
	}

	/**
	 * Check if such locale exists
	 * @param string $localeIdentifier
	 * @return boolean
	 */
	public function exists($localeIdentifier, $throws = true)
	{
		$locales = $this->getLocales();
		
		if (array_key_exists($localeIdentifier, $locales)) {
			return true;
		}

		if ($throws) {
			throw new Exception\RuntimeException("Locale '$localeIdentifier' is not defined.");
		}

		return false;
	}

	/**
	 * Check if such locale exists and return its data
	 * @param string $localeIdentifier
	 * @return Locale
	 * @throws Exception if such locale is not defined
	 */
	public function getLocale($localeIdentifier)
	{
		$this->exists($localeIdentifier, true);

		return $this->locales[$localeIdentifier];
	}

	/**
	 * Adds locale detector
	 * @param Detector\DetectorInterface $detector
	 */
	public function addDetector(Detector\DetectorInterface $detector)
	{
		$this->detectors[] = $detector;
	}

	/**
	 * Adds locale storage
	 * @param Storage\StorageInterface $storage
	 */
	public function addStorage(Storage\StorageInterface $storage)
	{
		$this->storages[] = $storage;
	}

	/**
	 * Set current locale by locale object or ID
	 * @param mixed $locale
	 * @throws Exception if such locale is not defined
	 */
	public function setCurrent($locale)
	{
		$localeIdentifier = null;

		if ($locale instanceof LocaleInterface) {
			$localeIdentifier = $locale->getId();
		} else {
			$localeIdentifier = $locale;
		}

		$this->exists($localeIdentifier, true);
		$this->current = $this->locales[$localeIdentifier];
	}

	/**
	 * Drops the current locale
	 */
	public function dropCurrent()
	{
		$this->current = null;
	}

	/**
	 * Get current locale
	 * @return Locale
	 */
	public function getCurrent()
	{
		return $this->current;
	}

	/**
	 * Prettier getter
	 *
	 * @return LocaleInterface
	 */
	public function getCurrentLocale()
	{
		return $this->getCurrent();
	}

	/**
	 * Returns array of defined locales
	 * @return LocaleInterface[]
	 */
	public function getLocales()
	{
		return $this->locales;
	}

	/**
	 * Get list of active locale objects
	 * @return array
	 */
	public function getActiveLocales()
	{
		$activeLocales = array();
		
		$locales = $this->getLocales();

		foreach ($locales as $id => $locale) {
			if ($locale->isActive()) {
				$activeLocales[$id] = $locale;
			}
		}

		return $activeLocales;
	}

	/**
	 * Detects current locale
	 * 
	 * @param Request $request
	 * @param Response | null $response
	 * 
	 * @throws Exception if locale was not detected
	 */
	public function detect(Request $request, Response $response = null)
	{
		$localeId = null;

		/* @var $detector Detector\DetectorInterface */
		foreach ($this->detectors as $detector) {
			
			$localeId = $detector->detect($request);
			
			if ( ! empty($localeId)) {
				
				$exists = $this->exists($localeId, false);
				
				if ($exists && ($this->processInactive || $this->isActive($localeId))) {
					$this->setCurrent($localeId);
					break;
				}
			}
		}

		if (empty($localeId)) {
			throw new Exception\RuntimeException("Could not detect locale for request '{$request->getBaseUrl()}'");
		}

		/* @var $storage Storage\StorageInterface */

		// @FIXME: there is no response object here.
		foreach ($this->storages as $storage) {
			$storage->store($request, $response, $localeId);
		}
	}

	/**
	 * Check is locale specified by id active
	 * @param type $localeId
	 * @return boolean
	 */
	public function isActive($localeId)
	{
		$locale = $this->getLocale($localeId, false);

		if ($locale instanceof LocaleInterface) {
			return $locale->isActive();
		}

		return false;
	}

	/**
	 * 
	 */
	public function processInactiveLocales()
	{
		$this->processInactive = true;
	}

}
