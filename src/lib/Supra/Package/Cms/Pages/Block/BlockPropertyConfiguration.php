<?php

namespace Supra\Package\Cms\Pages\Block;

use Supra\Package\Cms\Editable\EditableInterface;
use Supra\Editable;
use Supra\Uri\PathConverter;
use Supra\Editable\SelectVisual;

class BlockPropertyConfiguration
{
	protected $name;
	protected $editable;
	protected $defaultValue;

	protected $group;

	public function __construct($name, EditableInterface $editable, $label, $defaultValue, array $options = array())
	{
		$this->name = $name;
		$this->editable = $editable;
		$this->label = $label;
		$this->defaultValue = $defaultValue;

		if ( ! empty($options)) {
			$this->options = array_replace($this->options, $options);
		}
	}

	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return EditableInterface
	 */
	public function getEditable()
	{
		return $this->editable;
	}

	/**
	 * @param string $localeId
	 * @return mixed
	 */
	public function getDefaultValue($localeId)
	{
		if (! is_array($this->defaultValue)) {
			return $this->defaultValue;
		}

		return isset($this->defaultValue[$localeId]) 
				? $this->defaultValue[$localeId]
				: null;
	}
//
//	/**
//	 * @var string
//	 */
//	public $name;
//
//	/**
//	 * @var string
//	 */
//	public $editable;
//
//	/**
//	 * Generated editable instance
//	 * @var EditableInterface
//	 */
//	public $editableInstance;
//
//	/**
//	 * @var string
//	 */
//	public $label;
//
//	/**
//	 * @var string
//	 */
//	public $default;
//
//	/**
//	 * For Select, SelectVisual and Slideshow editables
//	 * @var array
//	 */
//	public $values = array();
//
//	/**
//	 * @var string
//	 */
//	public $group;
//
//	/**
//	 * @var boolean
//	 */
//	public $shared;
//
//	/**
//	 * @var array
//	 */
//	public $properties = array();
//
//	/**
//	 * @var array
//	 */
//	public $propertyGroups = array();
//
//	/**
//	 * @var string
//	 */
//	public $description;
//
//	/**
//	 * Hash table for editable additional properties
//	 * @var array
//	 */
//	public $additionalParameters = array();
//
//	public function configure()
//	{
//		$this->editableInstance = Loader::getClassInstance($this->editable, 'Supra\Editable\EditableInterface');
//		$this->editableInstance->setLabel($this->label);
//		$this->editableInstance->setDefaultValue($this->default);
//
//		// setting predefined values for select boxes
//		// @FIXME: not nice
//		if ($this->editableInstance instanceof Editable\Select
//				|| $this->editableInstance instanceof Editable\SelectVisual) {
//
//			if (method_exists($this->editableInstance, 'setValues')) {
//				$this->editableInstance->setValues($this->values);
//			}
//		}
//
//		//FIXME: not nice. Editable might inform about its additionals maybe?
//		foreach ($this->additionalParameters as $name => $value) {
//
//			$methodName = 'set' . $name;
//
//			if (method_exists($this->editableInstance, $methodName)) {
//				$this->editableInstance->$methodName($value);
//			} else {
//				\Log::warn("No additional parameter setter found for editable {$this->editable} with name {$name}");
//			}
//		}
//
//		if ( ! empty($this->group)) {
//			$this->editableInstance->setGroupId($this->group);
//		}
//
//		if ( ! empty($this->description)) {
//			$this->editableInstance->setDescription($this->description);
//		}
//	}

	/**
	 *
	 * @param \Supra\Editable\EditableInterface $editable
	 * @param type $name
	 * @return \Supra\Controller\Pages\Configuration\BlockPropertyConfiguration
	 */
	public function fillFromEditable(EditableInterface $editable, $name)
	{
		$this->name = $name;
		$this->editableInstance = $editable;
		$this->editable = get_class($editable);
		$this->label = $editable->getLabel();
		$this->default = $editable->getDefaultValue();
		$this->group = $editable->getGroupId();

		return $this;
	}

	public function configurePathsUsingContext($context)
	{
		$editable = $this->editableInstance;

		if ($editable instanceof Editable\SelectVisual) {
			$this->processSelectVisual($this, $context);
		}

		else if ($editable instanceof Editable\Slideshow) {

			foreach ($this->properties as $subProperty) {

				$subEditable = $subProperty->editableInstance;

				if ($subEditable instanceof \Supra\Editable\SelectVisual) {
					$this->processSelectVisual($subProperty, $context);
				}
			}
		}

		else if ($editable instanceof Editable\MediaGallery) {

			if (isset($this->additionalParameters['layouts'])
					&& ! empty($this->additionalParameters['layouts'])) {


				$layoutConfigurations = $this->additionalParameters['layouts'];
				foreach ($layoutConfigurations as $layoutConfiguration) {
					$layoutConfiguration->file = $this->getFileFullPath($layoutConfiguration->file, $context);
				}
			}
		}
	}

	/**
	 * SelectVisual Editable's icon paths must be rewrited
	 * with proper webroot + current-component-folder paths
	 */
	private function processSelectVisual($configuration, $context)
	{
		foreach ($configuration->values as &$value) {
			if ( ! empty($value[SelectVisual::PROPERTY_ICON])) {
				$value[SelectVisual::PROPERTY_ICON] = $this->getFileWebPath($value[SelectVisual::PROPERTY_ICON], $context);
			}

			// SelectVisual values can be nested,
			// but for now, only two levels are supported
			if (isset($value[SelectVisual::PROPERTY_TYPE])
					&& $value[SelectVisual::PROPERTY_TYPE] == SelectVisual::TYPE_GROUP) {
				if ( ! empty($value['values'])) {
					foreach ($value['values'] as &$subValue) {
						$subValue[SelectVisual::PROPERTY_ICON] = $this->getFileWebPath($subValue[SelectVisual::PROPERTY_ICON], $context);
					}
				}
			}
		}

		$configuration->editableInstance->setValues($configuration->values);
	}

	/**
	 *
	 * @param string $file
	 * @param string $context
	 * @return string
	 */
	protected function getFileWebPath($file, $context = null)
	{
		if (strpos($file, '/') !== 0) {
			return PathConverter::getWebPath($file, $context);
		} else {
			$file = SUPRA_WEBROOT_PATH . $file;
			return PathConverter::getWebPath($file);
		}
	}

	protected function getFileFullPath($file, $context = null)
	{
		if (strpos($file, '/') !== 0) {
			return SUPRA_WEBROOT_PATH . PathConverter::getWebPath($file, $context);
		} else {
			return $file;
		}
	}

	/**
	 *
	 */
	protected function processPropertyGroupConfigurations()
	{
		$propertyGroups = array();

		foreach ($this->propertyGroups as $group) {

			if ($group instanceof BlockPropertyGroupConfiguration) {

				if (isset($propertyGroups[$group->id])) {
					\Log::warn('Property group with id "' . $group->id . '" already exist in property group list. Skipping group. Configuration: ', $group);
					continue;
				}

				if ( ! empty($group->icon)) {
					$group->icon = $this->getFileWebPath($group->icon);
				}

				$propertyGroups[$group->id] = $group;

			} else {
				\Log::warn('Group should be instance of BlockPropertyGroupConfiguration ', $group);
			}
		}

		$this->propertyGroups = array_values($propertyGroups);
	}

}
