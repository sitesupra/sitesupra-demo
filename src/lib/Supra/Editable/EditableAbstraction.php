<?php

namespace Supra\Editable;

use Supra\Loader;

/**
 * Abstract class for editable content classes
 */
abstract class EditableAbstraction implements EditableInterface
{
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
	protected $defaultValue;

	/**
	 * @param string $label
	 */
	public function __construct($label)
	{
		$this->setLabel($label);
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
	
	public function getFilters()
	{
		return $this->filters;
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
	
	/**
	 * Which fields to serialize
	 * @return array
	 */
	public function __sleep()
	{
		$fields = array(
			'label',
			'defaultValue'
		);
		
		return $fields;
	}

	/**
	 * @return array
	 */
	public function getAdditionalParameters()
	{
		return array();
	}
}
