<?php

namespace Supra\Locale\Storage;

use Supra\Locale\Data;

/**
 * Locale storage abstraction
 */
abstract class StorageAbstraction
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