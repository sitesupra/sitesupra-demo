<?php

namespace Supra\Package\Cms\Editable;

// @TODO: this should be used instead Editable::addEditable / getEditable();

//class EditableCollection
//{
//	/**
//	 * @var array
//	 */
//	protected $editables = array();
//
//	/**
//	 * @param array $editables
//	 */
//	public function __construct(array $editables = array())
//	{
//		$this->editables = $editables;
//	}
//
//	/**
//	 * @param string $name
//	 * @return EditableInterface
//	 * @throws \RuntimeException
//	 */
//	public function get($name)
//	{
//		if (! $this->has($name)) {
//			throw new \RuntimeException("There is no [{$name}] editable.");
//		}
//
//		return $this->editables[$name];
//	}
//
//	/**
//	 * @param string $name
//	 * @return bool
//	 */
//	public function has($name)
//	{
//		return isset($this->editables[$name]);
//	}
//
//	/**
//	 * @param string $name
//	 * @param EditableInterface $editable
//	 * @throws \RuntimeException
//	 */
//	public function add($name, EditableInterface $editable)
//	{
//		if ($this->has($name)) {
//			throw new \RuntimeException("Editable [{$name}] already exists.");
//		}
//
//		$this->editables[$name] = $editable;
//	}
//
//	/**
//	 * @param string $name
//	 * @param EditableInterface $editable
//	 */
//	public function set($name, EditableInterface $editable)
//	{
//		$this->editables[$name] = $editable;
//	}
//}