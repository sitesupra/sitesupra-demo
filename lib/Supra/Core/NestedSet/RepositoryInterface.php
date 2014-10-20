<?php

namespace Supra\Core\NestedSet;

/**
 * The nested set and Doctrine entity repositories must implement this interface
 */
interface RepositoryInterface
{
	/**
	 * @return \Supra\Core\NestedSet\RepositoryAbstraction
	 */
	public function getNestedSetRepository();
}