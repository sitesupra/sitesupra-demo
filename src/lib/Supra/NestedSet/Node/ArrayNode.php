<?php

namespace Supra\NestedSet\Node;

use Supra\NestedSet\ArrayRepository;

/**
 * 
 */
class ArrayNode extends NodeAbstraction
{
	/**
	 * @var ArrayRepository
	 */
	protected $repository;

	public function setRepository(ArrayRepository $repository)
	{
		return parent::setRepository($repository);
	}
}