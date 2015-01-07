<?php

namespace Supra\Package\Cms\Pages\Finder\Organizer\Iterator;

class RecursiveLocalizationIterator implements \RecursiveIterator
{

	protected $data;
	protected $position = 0;
	protected $depth = 0;

	/**
	 * @param array $data 
	 */
	public function __construct(array $data)
	{
		$this->data = $data;
	}

	/**
	 * @return boolean 
	 */
	public function valid()
	{
		return isset($this->data[$this->position]);
	}

	/**
	 * @return boolean 
	 */
	public function hasChildren()
	{
		return ($this->data[$this->position]['children'] instanceof RecursiveLocalizationIterator);
	}

	public function next()
	{
		$this->position ++;
	}

	/**
	 * @return \Supra\Package\Cms\Entity\PageLocalization
	 */
	public function current()
	{
		return $this->data[$this->position]['localization'];
	}

	/**
	 * @return RecursiveLocalizationIterator 
	 */
	public function getChildren()
	{
		return $this->data[$this->position]['children'];
	}

	public function rewind()
	{
		$this->position = 0;
	}

	/**
	 * @return integer 
	 */
	public function key()
	{
		return $this->position;
	}

	/**
	 * @return integer 
	 */
	public function getDepth()
	{
		return $this->depth;
	}

	/**
	 * @param integer $depth 
	 */
	public function setDepth($depth)
	{
		$this->depth = $depth;
	}

}