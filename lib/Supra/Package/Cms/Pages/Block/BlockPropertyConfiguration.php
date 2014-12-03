<?php

namespace Supra\Package\Cms\Pages\Block;

use Supra\Package\Cms\Editable\EditableInterface;

class BlockPropertyConfiguration
{
	protected $name;
	protected $editable;
//	protected $defaultValue;

	/**
	 * @param string $name
	 * @param EditableInterface $editable
	 * @param mixed $defaultValue
	 */
	public function __construct(
			$name,
			EditableInterface $editable
//			EditableInterface $editable,
//			$defaultValue
	) {
		$this->name = $name;
		$this->editable = $editable;
//		$this->defaultValue = $defaultValue;
	}

	/**
	 * @return string
	 */
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

//	/**
//	 * @param string $localeId
//	 * @return mixed
//	 */
//	public function getDefaultValue($localeId)
//	{
//		if (! is_array($this->defaultValue)) {
//			return $this->defaultValue;
//		}
//
//		return isset($this->defaultValue[$localeId])
//				? $this->defaultValue[$localeId]
//				: null;
//	}

}
