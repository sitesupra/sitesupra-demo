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

	/**
	 * Wrapper for addLocale().
	 *
	 * @param LocaleInterface $locale
	 */
	public function add(LocaleInterface $locale)
	{
		$this->addLocale($locale);
	}

	/**
	 * @param LocaleInterface $locale
	 * @throws \InvalidArgumentException
	 */
	public function addLocale(LocaleInterface $locale)
	{
		$id = $locale->getId();

		if (empty($id)) {
			throw new \InvalidArgumentException('Empty locale ID is not allowed.');
		}

		if ($this->hasLocale($id)) {
			throw new \InvalidArgumentException(sprintf('Locale [%s] is already defined.', $id));
		}

		$this->locales[$id] = $locale;
	}

	/**
	 * Wrapper for getLocale().
	 *
	 * @param string $id
	 * @return bool
	 */
	public function has($id)
	{
		return $this->hasLocale($id);
	}

	/**
	 * @param string $id
	 * @return bool
	 */
	public function hasLocale($id)
	{
		return isset($this->locales[$id]);
	}

//	/**
//	 * @deprecated
//	 *
//	 * Check if specified locale is defined.
//	 * Will throw an exception if is not, and $throw is true.
//	 *
//	 * @param string $localeIdentifier
//	 * @param bool $throw
//	 * @return bool
//	 */
//	public function exists($localeId, $throw = true)
//	{
//		if (! $this->has($localeId)) {
//			if ($throw) {
//				throw new RuntimeException(sprintf(
//						'Locale [%s] is not defined.',
//						$localeId
//				));
//			}
//		}
//
//		return true;
//	}

	/**
	 * Wrapper for getLocale().
	 * 
	 * @param string $id
	 * @return Locale
	 */
	public function get($id)
	{
		return $this->getLocale($id);
	}

	/**
	 * @param string $id
	 * @return Locale
	 * @throws RuntimeException
	 */
	public function getLocale($id)
	{
		if (! $this->hasLocale($id)) {
			throw new \RuntimeException(sprintf(
					'Locale [%s] is not defined.', $id
			));
		}

		return $this->locales[$id];
	}

	/**
	 * Adds locale detector.
	 * 
	 * @param Detector\DetectorInterface $detector
	 */
	public function addDetector(Detector\DetectorInterface $detector)
	{
		$this->detectors[] = $detector;
	}

	/**
	 * Adds locale storage.
	 * 
	 * @param Storage\StorageInterface $storage
	 */
	public function addStorage(Storage\StorageInterface $storage)
	{
		$this->storages[] = $storage;
	}

	/**
	 * Set current locale by locale object or ID.
	 * 
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

		$this->current = $this->getLocale($localeIdentifier);
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
				
				if ($this->hasLocale($localeId) && ($this->processInactive || $this->isActive($localeId))) {
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
	 * Setups the internal state for detect() method
	 * @TODO: kinda bad solution, must refactor.
	 */
	public function processInactiveLocales()
	{
		$this->processInactive = true;
	}

	/**
	 * @TODO: shouldn't be located here.
	 * 
	 * @internal
	 * @return array
	 */
	public function getLocaleArray()
	{
		$localeArray = array();

		foreach ($this->getLocales() as $locale) {

			$country = $locale->getCountry();

			if (! isset($localeArray[$country])) {
				$localeArray[$country] = array(
					'title' => $country,
					'languages' => array()
				);
			}

			$localeArray[$country]['languages'][] = array(
				'id' => $locale->getId(),
				'title' => $locale->getTitle(),
				'flag' => $locale->getProperty('flag')
			);
		}

		return array_values($localeArray);
	}
}
