<?php

namespace Supra\Search\Result\Abstraction;

use Supra\Search\Result\SearchResultItemInterface;

abstract class SearchResultItemAbstraction implements SearchResultItemInterface
{
	/**
	 * @var string
	 */
	protected $uniqueId;

	/**
	 * @return string
	 */
	public function getUniqueId()
	{
		return $this->uniqueId;
	}

	/**
	 * @param string $uniqueId 
	 */
	public function setUniqueId($uniqueId)
	{
		$this->uniqueId = $uniqueId;
	}

}
