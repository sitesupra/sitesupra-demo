<?php

namespace Supra\NestedSet\Node;

use Supra\NestedSet\ArrayRepository;

/**
 * Nested set node for array repository
 */
class ArrayNode extends NodeAbstraction
{
	/**
	 * @var ArrayRepository
	 */
	protected $repository;

	/**
	 * @param ArrayRepository $repository
	 * @return ArrayNode
	 */
	public function setRepository(ArrayRepository $repository)
	{
		return parent::setRepository($repository);
	}
}