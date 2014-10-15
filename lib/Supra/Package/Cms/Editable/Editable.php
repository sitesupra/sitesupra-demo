<?php

namespace Supra\Package\Cms\Editable;

use Supra\Package\Cms\Editable\Transformer\ValueTransformerInterface;
use Supra\Package\Cms\Editable\Filter\FilterInterface;

abstract class Editable implements EditableInterface
{
	/**
	 * @var FilterInterface[] 
	 */
	protected $filters = array();

	/**
	 * @var ValueTransformerInterface[]
	 */
	protected $transformers = array();

	/**
	 * @var string
	 */
	protected $label;

	/**
	 * @var string
	 */
	protected $description;

	/**
	 * @var mixed
	 */
	protected $defaultValue;

	/**
	 * @var string
	 */
	protected $groupId;

	/**
	 * @param ValueTransformerInterface $transformer
	 * @throws \InvalidArgumentException if value transformer already exists in collection.
	 */
	public function addEditorValueTransformer(ValueTransformerInterface $transformer)
	{
		$class = get_class($transformer);

		if (isset($this->transformers[$class])) {

			//@FIXME
			return null;
			throw new \InvalidArgumentException(
					"Value transformer [{$class}] is already in collection."
			);
		}
		
		$this->transformers[$class] = $transformer;
	}

	/**
	 * @return mixed
	 */
	public function getEditorValue()
	{
		$value = $this->getRawValue();

		foreach ($this->transformers as $transformer) {
			$value = $transformer->transform($value);
		}

		return $value;
	}

	/**
	 * @param mixed $value
	 */
	public function setEditorValue($value)
	{
		foreach ($this->transformers as $transformer) {
			$value = $transformer->reverseTransform($value);
		}

		$this->setRawValue($value);
	}

	/**
	 * @param FilterInterface $filter
	 * @throws \InvalidArgumentException if filter already exists in filters collection.
	 */
	public function addFilter(FilterInterface $filter)
	{
		$class = get_class($filter);

		if (isset($this->filters[$class])) {
			
			//@FIXME
			return null;
			throw new \InvalidArgumentException(
					"Filter [{$class}] is already in collection."
			);
		}

		$this->filters[$class] = $filter;
	}

	/**
	 * @return mixed
	 */
	public function getFilteredValue()
	{
		$value = $this->getRawValue();

		foreach ($this->filters as $filter) {
			$value = $filter->filter($value);
		}

		return $value;
	}

	/**
	 * @param mixed $value
	 */
	public function setRawValue($value)
	{
		$this->value = $value;
	}

	/**
	 * @return mixed
	 */
	public function getRawValue()
	{
		return $this->value;
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
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * @param string $description
	 */
	public function setDescription($description)
	{
		$this->description = $description;
	}

	/**
	 * @return array
	 */
	public function getAdditionalParameters()
	{
		return array();
	}

	/**
	 * @param string $localeId
	 * @return mixed
	 */
	public function getDefaultValue($localeId = null)
	{
		return null;
	}

	/**
	 * @param mixed $value
	 */
	public function setDefaultValue($value)
	{
		$this->defaultValue = $value;
	}

	/**
	 * @return string
	 */
	public function getGroupId()
	{
		return $this->groupId;
	}

	/**
	 * @param string $groupLabel
	 */
	public function setGroupId($groupId)
	{
		$this->groupId = $groupId;
	}
	
}
