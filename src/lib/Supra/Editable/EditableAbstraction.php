<?php

namespace Supra\Editable;

/**
 * Abstract class for editable content classes
 */
abstract class EditableAbstraction implements EditableInterface
{
	/**
	 * Default filter classes for content by action
	 * @var array
	 */
	protected static $defaultFilters = array();

	/**
	 * Array of content filters
	 * @var array
	 */
	protected $filters = array();

	/**
	 * @return mixed
	 */
	protected $content;

	/**
	 * Content label
	 * @var string
	 */
	protected $label;
	
	/**
	 * Default value
	 * @var mixed
	 */
	protected $defaultValue = '';

	/**
	 * @param string $label
	 */
	public function __construct($label)
	{
		$this->setLabel($label);
		$filterClass = null;

		// Fill in the default filters
		foreach (static::$defaultFilters as $filterClass) {

			if ( ! class_exists($filterClass)) {
				throw new Exception\FilterNotFound("Filter '{$filterClass}' was not found", $this);
			}

			$filter = new $filterClass();

			if ( ! $filter instanceof Filter\FilterInterface) {
				throw new Exception\FilterNotFound("Filter '{$filterClass}' does not implement the filter interface", $this);
			}

			$this->filters[] = $filter;
		}
	}

	/**
	 * Loads content data
	 * @return mixed
	 */
	public function getContent()
	{
		return $this->content;
	}

	/**
	 * Sets content data
	 * @param mixed $content
	 */
	public function setContent($content)
	{
		$this->content = $content;
	}

	/**
	 * Adds filter for the editable content
	 * @param Filter\FilterInterface $filter
	 */
	public function addFilter(Filter\FilterInterface $filter)
	{
		$this->filters[] = $filter;
	}

	/**
	 * Get filtered value for the editable content by action
	 * @param string $action
	 * @return string
	 */
	public function getFilteredValue()
	{
		$content = $this->content;

		// Filter the content
		foreach ($this->filters as $filter) {
			$content = $filter->filter($content);
		}

		return $content;
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * @param string $label
	 */
	public function setLabel($label)
	{
		$this->label = $label;
	}
	
	/**
	 * @return mixed 
	 */
	public function getDefaultValue()
	{
		return $this->defaultValue;
	}

	/**
	 * @param mixed $value 
	 */
	public function setDefaultValue($value)
	{
		$this->defaultValue = $value;
	}

}
