<?php

namespace Supra\Locale;

/**
 * Localization
 */
class Data
{

	/**
	 * Instance
	 * @var Data
	 */
	protected static $instance;

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
	 * @var string
	 */
	protected $current;

	/**
	 * Singleton pattern
	 * @return Data
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Add locale data
	 * @param string $localeIdentifier
	 * @param array $localeData
	 * @throws Exception if locale identifier is not a string
	 */
	public function add($localeIdentifier, array $localeData)
	{
		if ( ! \is_string($localeIdentifier)) {
			throw new Exception("Locale identifier must be string, " . \gettype($localeIdentifier) . " provided");
		}
		$this->locales[$localeIdentifier] = $localeData;
	}

	/**
	 * Check if such locale exists
	 * @param string $localeIdentifier
	 * @return boolean
	 */
	public function exists($localeIdentifier, $throws = true)
	{
		if (\array_key_exists($localeIdentifier, $this->locales)) {
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
	 * @return array
	 * @throws Exception if such locale is not defined
	 */
	public function getData($localeIdentifier)
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
	 * @param Detector\DetectorInterface $detector
	 */
	public function addStorage(Detector\DetectorInterface $detector)
	{
		$this->detectors[] = $detector;
	}

	/**
	 * Set current locale
	 * @param string $localeIdentifier
	 * @throws Exception if such locale is not defined
	 */
	public function setCurrent($localeIdentifier)
	{
		$this->exists($localeIdentifier, true);
		$this->current = $localeIdentifier;
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
	 * @return string
	 */
	public function getCurrent()
	{
		return $this->current;
	}

	public function detect(\Supra\Controller\Request\RequestInterface $request, \Supra\Controller\Response\ResponseInterface $response)
	{
		/* @var $detector Detector\DetectorInterface */
		foreach ($this->detectors as $detector) {
			$locale = $detector->detect($request, $response);
			if ( ! empty($locale)) {
				$exists = $this->exists($locale, false);
				if ($exists) {
					$this->setCurrent($locale);
					break;
				}
			}
		}
		
		if (empty($locale)) {
			throw new Exception("Could not detect locale for request '{$request->getActionString()}'");
		}

		/* @var $detector Storage\StorageInterface */
		foreach ($this->storage as $storage) {
			$locale = $detector->store($locale);
			if ( ! empty($locale)) {
				$this->setCurrent($locale);
				break;
			}
		}

	}
}