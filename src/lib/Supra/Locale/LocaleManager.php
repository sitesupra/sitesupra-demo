<?php

namespace Supra\Locale;

use Supra\Request\RequestInterface;
use Supra\Response\ResponseInterface;

/**
 * Localization
 */
class LocaleManager
{
	/**
	 * Locales defined
	 * @var array
	 */
	protected $locales = array();

	/**
	 * Detectors defined
	 * @var Detector\DetectorInterface[]
	 */
	protected $detectors = array();

	/**
	 * Current locale identifier
	 * @var Locale
	 */
	protected $current;
	
	/**
	 * Weither to process inactive locales, or not
	 * @var boolean
	 */
	protected $processInactive = false;

	/**
	 * Add locale data
	 * @param Locale $locale
	 * @throws Exception if Locale's ID is empty
	 */
	public function add(Locale $locale)
	{
		$id = $locale->getId();
		if (empty($id)) {
			throw new Exception("Locale ID is not defined");
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
		if (array_key_exists($localeIdentifier, $this->locales)) {
			return true;
		}
		
		if ($throws) {
			throw new Exception("Locale '$localeIdentifier' is not defined");
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
		$this->storage[] = $storage;
	}

	/**
	 * Set current locale by locale object or ID
	 * @param mixed $locale
	 * @throws Exception if such locale is not defined
	 */
	public function setCurrent($locale)
	{
		$localeIdentifier = null;
		
		if ($locale instanceof Locale) {
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
	 * Returns array of defined locales
	 * @return array 
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
		
		foreach ($this->locales as $id => $locale) {
			if ($locale->isActive()) {
				$activeLocales[$id] = $locale;
			}
		}
		
		return $activeLocales;
	}
	
	/**
	 * Detects current locale
	 * @param RequestInterface $request
	 * @param ResponseInterface $response 
	 * @throws Exception if locale was not detected
	 */
	public function detect(RequestInterface $request, ResponseInterface $response)
	{
		$localeId = null;
		
		/* @var $detector Detector\DetectorInterface */
		foreach ($this->detectors as $detector) {
			$localeId = $detector->detect($request, $response);
			if ( ! empty($localeId)) {
				$exists = $this->exists($localeId, false);
				if ($exists && ($this->processInactive || $this->isActive($localeId))) {
					\Log::debug("Locale '{$localeId}' detected by ".get_class($detector));
					$this->setCurrent($localeId);
					break;
				}
			}
		}
		
		if (empty($localeId)) {
			throw new Exception("Could not detect locale for request '{$request->getActionString()}'");
		}

		/* @var $storage Storage\StorageInterface */
		foreach ($this->storage as $storage) {
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
		
		if ($locale instanceof Locale) {
			return $locale->isActive();
		}
		
		return false;
	}
	
	public function processInactiveLocales()
	{
		$this->processInactive = true;
	}
	
}