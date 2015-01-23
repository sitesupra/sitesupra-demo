<?php

namespace Supra\Package\Cms\Editable;

use Supra\Package\Cms\Editable\Transformer\ValueTransformerInterface;
use Supra\Package\Cms\Editable\Filter\FilterInterface;

abstract class Editable implements EditableInterface
{
	/**
	 * @var array
	 */
	private static $aliases = array(
		'string'		=> '\Supra\Package\Cms\Editable\String',
		'inline_string'	=> '\Supra\Package\Cms\Editable\InlineString',
		'text'			=> '\Supra\Package\Cms\Editable\Textarea',
		'inline_text'	=> '\Supra\Package\Cms\Editable\InlineTextarea',
		'html'			=> '\Supra\Package\Cms\Editable\Html',
		'checkbox'		=> '\Supra\Package\Cms\Editable\Checkbox',
		'number'		=> '\Supra\Package\Cms\Editable\Number',
		'link'			=> '\Supra\Package\Cms\Editable\Link',
		'image'			=> '\Supra\Package\Cms\Editable\Image',
		'inline_map'	=> '\Supra\Package\Cms\Editable\InlineMap',
		'gallery'		=> '\Supra\Package\Cms\Editable\Gallery',
		'datetime'		=> '\Supra\Package\Cms\Editable\DateTime',
		'keywords'		=> '\Supra\Package\Cms\Editable\Keywords',
		'select'		=> '\Supra\Package\Cms\Editable\Select',
		'select_list'	=> '\Supra\Package\Cms\Editable\SelectList',
		'video'			=> '\Supra\Package\Cms\Editable\Video',
		'inline_image'	=> '\Supra\Package\Cms\Editable\InlineImage',
		// @TODO: types listed below:
//		'inline_image' => '',
//		'inline_media'	=> '',
//		'select_visual' => '',
	);

	/**
	 * @var FilterInterface[]
	 */
	protected $viewFilters = array();

	/**
	 * @var ValueTransformerInterface[]
	 */
	protected $transformers = array();

	/**
	 * @var array
	 */
	protected $options = array();

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
			throw new \InvalidArgumentException(
					"Value transformer [{$class}] is already in collection."
			);
		}

		$this->transformers[$class] = $transformer;
	}

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	public function toEditorValue($value)
	{
		foreach ($this->transformers as $transformer) {
			$value = $transformer->transform($value);
		}

		return $value;
	}

	/**
	 * @TODO: bad naming
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function fromEditorValue($value)
	{
		foreach ($this->transformers as $transformer) {
			$value = $transformer->reverseTransform($value);
		}

		return $value;
	}

	/**
	 * @param mixed $value
	 * @param array $options
	 * @return mixed
	 */
	public function toViewValue($value, array $options = array())
	{
		foreach ($this->viewFilters as $filter) {
			$value = $filter->filter($value, $options);
		}

		return $value;
	}

	/**
	 * @param FilterInterface $filter
	 * @throws \InvalidArgumentException if filter already exists in filters collection.
	 */
	public function addViewFilter(FilterInterface $filter)
	{
		$class = get_class($filter);

		if (isset($this->viewFilters[$class])) {
			throw new \InvalidArgumentException(
					"Filter [{$class}] is already in collection."
			);
		}

		$this->viewFilters[$class] = $filter;
	}

	/**
	 * @return null|string
	 */
	public function getLabel()
	{
		if (! empty($this->options['label'])) {
			return $this->options['label'];
		}

		return null;
	}

	/**
	 * @return null|string
	 */
	public function getDescription()
	{
		if (! empty($this->options['description'])) {
			return $this->options['description'];
		}

		return null;
	}

	/**
	 * @param string $localeId
	 * @return mixed
	 */
	public function getDefaultValue($localeId = null)
	{
		if (! empty($this->options['default'])) {
			return $this->options['default'];
		}

		return null;
	}

	/**
	 * @return array
	 */
	public function getAdditionalParameters()
	{
		return array();
	}

//	/**
//	 * @param mixed $value
//	 */
//	public function setDefaultValue($value)
//	{
//		$this->defaultValue = $value;
//	}

	/**
	 * @return Editable
	 */
	public function getInstance()
	{
		return new static();
	}

	/**
	 * @param array $options
	 */
	public function setOptions(array $options)
	{
		$this->options = $options;
	}

	/**
	 * @param string $name
	 * @return Editable
	 * @throws \InvalidArgumentException
	 */
	public static function getEditable($name)
	{
		if (! isset(self::$aliases[$name])) {
			throw new \InvalidArgumentException(sprintf(
					'Unknown editable [%s]',
					$name
			));
		}

		return new self::$aliases[$name]();
	}

	/**
	 * @param string $name
	 * @param string $editableClass
	 * @throws \InvalidArgumentException
	 */
	public static function addEditable($name, $editableClass)
	{
		if (isset(self::$aliases[$name])) {
			throw new \InvalidArgumentException(sprintf(
					'Editable with name [%s] already exists.',
					$name
			));
		}

		self::$aliases[$name] = $editableClass;
	}
}
