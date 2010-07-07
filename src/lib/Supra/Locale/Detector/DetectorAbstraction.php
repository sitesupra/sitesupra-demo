<?php

namespace Supra\Locale\Detector;

use Supra\Locale\Data;

/**
 * Locale detector abstraction
 */
abstract class DetectorAbstraction implements DetectorInterface
{
	/**
	 * The locale data provider
	 * @var Data
	 */
	protected $data;

	/**
	 * Sets locale data provider
	 * @param Data $data
	 */
	public function setData(Data $data)
	{
		$this->data = $data;
	}
}