<?php

namespace Supra\Editable;

/**
 * Abstract class for editable content classes
 */
abstract class EditableAbstraction implements EditableInterface
{
	// Action types for choosing the content filter
	const ACTION_VIEW = 'view';
	const ACTION_EDIT = 'edit';
	const ACTION_PREVIEW = 'preview';
	
	/**
	 * Default filter classes for content by action
	 * @var array
	 */
	protected static $defaultFilters = array(
		self::ACTION_VIEW => 'Supra\Editable\Filter\Raw',
		self::ACTION_EDIT => 'Supra\Editable\Filter\Raw',
		self::ACTION_PREVIEW => 'Supra\Editable\Filter\Raw'
	);
	
	/**
	 * @return mixed
	 */
	protected $data;

	/**
	 * Content label
	 * @var string
	 */
	protected $label;
	
	/**
	 * @param string $label
	 */
	public function __construct($label)
	{
		$this->setLabel($label);
	}
	
	/**
	 * Get JavaScript editor name
	 * @return string
	 */
	abstract public function getEditorName();
	
	/**
	 * Loads content data
	 * @return mixed
	 */
	public function getData()
	{
		return $this->data;
	}
	
	/**
	 * Sets content data
	 * @param mixed $data
	 */
	public function setData($data)
	{
		$this->data = $data;
	}
	
	/**
	 * Get filter object for the passed action
	 * @param string $action
	 * @return Filter\FilterInterface
	 * @throws Exception\FilterNotFound
	 */
	protected function getFilter($action)
	{
		$filterClass = null;
		
		// Try the extended default filter definition, Raw otherwise
		if (isset(static::$defaultFilters[$action])) {
			$filterClass = static::$defaultFilters[$action];
		} elseif (isset(self::$defaultFilters[$action])) {
			$filterClass = self::$defaultFilters[$action];
		}
		
		if (empty($filterClass)) {
			throw new Exception\FilterNotFound("Filter not defined", $this, $action);
		}
		
		if ( ! class_exists($filterClass)) {
			throw new Exception\FilterNotFound("Filter {$filterClass} was not found", $this, $action);
		}
		
		$filter = new $filterClass();
		
		if ( ! $filter instanceof Filter\FilterInterface) {
			throw new Exception\FilterNotFound("Filter {$filterClass} does not implement the filter interface", $this, $action);
		}
		
		return $filter;
	}
	
	/**
	 * Get filtered value for the editable content by action
	 * @param string $action
	 * @return string
	 */
	public function getFilteredValue($action = self::ACTION_VIEW)
	{
		$filter = $this->getFilter($action);
		$value = $filter->filter($this);
		
		return $value;
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

}
