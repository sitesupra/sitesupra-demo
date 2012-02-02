<?php

namespace Supra\Search\Result\Abstraction;

use Supra\Search\Result\SearchResultItemInterface;
use Supra\Search\Result\Exception;

abstract class SearchResultItemAbstraction implements SearchResultItemInterface
{

	/**
	 * @var string
	 */
	protected $uniqueId;

	/**
	 *
	 * @var string
	 */
	protected $class;

	/**
	 * @return string
	 */
	public function getUniqueId()
	{
		if (empty($this->uniqueId)) {
			throw new Exception\RuntimeException('Item id is not set.');
		}

		return $this->uniqueId;
	}

	/**
	 * @param string $uniqueId 
	 */
	public function setUniqueId($uniqueId)
	{
		$this->uniqueId = $uniqueId;
	}

	/**
	 * @return string
	 */
	public function getClass()
	{
		if (empty($this->class)) {
			throw new Exception\RuntimeException('Item class is not set.');
		}

		return $this->class;
	}

	/**
	 * @param string $class 
	 */
	public function setClass($class)
	{
		$this->class = $class;
	}

}
