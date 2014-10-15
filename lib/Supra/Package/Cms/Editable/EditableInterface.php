<?php

namespace Supra\Package\Cms\Editable;

use Supra\Package\Cms\Editable\Filter\FilterInterface;

/**
 * Interface for editable content class
 */
interface EditableInterface
{
	/**
	 * @return string
	 */
	public function getEditorType();

	/**
	 * @return string
	 */
	public function getLabel();

	/**
	 * @param string $label
	 */
	public function setLabel($label);

	/**
	 * @return string
	 */
	public function getDescription();

	/**
	 * @param string $description
	 */
	public function setDescription($description);

	/**
	 * @return array
	 */
	public function getAdditionalParameters();

	/**
	 * @param string $localeId
	 * @return mixed
	 */
	public function getDefaultValue($localeId = null);

	/**
	 * @param mixed $value
	 */
	public function setDefaultValue($value);

	/**
	 * @return string
	 */
	public function getGroupId();

	/**
	 * @param string $groupLabel
	 */
	public function setGroupId($groupId);

//	/**
//	 * @return mixed
//	 */
//	public function getContent();
//
//	/**
//	 * @param mixed $content
//	 */
//	public function setContent($content);

	/**
	 * @return mixed
	 */
	public function getFilteredValue();

	/**
	 * @param FilterInterface $filter
	 */
	public function addFilter(FilterInterface $filter);
}