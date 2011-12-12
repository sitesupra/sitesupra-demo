<?php

namespace Supra\Cms\CrudManager;

interface CrudRepositoryInterface
{

	/**
	 * @return array
	 */
	public function getEditableFields();

	/**
	 * @return array
	 */
	public function getListFields();

	/**
	 * @return boolean
	 */
	public function isSortable();

	/**
	 * @return boolean
	 */
	public function isDeletable();

	/**
	 * @return boolean
	 */
	public function isLocalized();

	/**
	 * @return boolean
	 */
	public function isCreatable();
}