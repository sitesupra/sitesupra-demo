<?php

namespace Supra\NestedSet\Node;

use Supra\NestedSet\Node\DoctrineNode;

/**
 * Interface for the entities
 */
interface EntityNodeInterface extends NodeInterface
{
	/**
	 * Get class name to get the repository for the nested set
	 * @return string
	 */
	public function getNestedSetRepositoryClassName();
	
	/**
	 * Nested set node setter
	 * @param DoctrineNode $nestedSetNode
	 */
	public function setNestedSetNode(DoctrineNode $nestedSetNode);
}
