<?php

namespace Supra\Cms\CrudManager;

use Supra\Validator\FilteredInput;

interface CrudEntityInterface
{
//
//	/**
//	 * @return array
//	 */
//	public function getListValues();

	/**
	 * @return array
	 */
	public function getEditValues();

	/**
	 * @param array $listValues
	 */
	public function setEditValues(FilteredInput $editValues, $newRecord = null);
}