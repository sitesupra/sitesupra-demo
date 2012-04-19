<?php

namespace Supra\NestedSet;

use Doctrine\ORM\EntityManager;

/**
 * Array nested set repository to keep and update Doctrine nodes locally
 */
class DoctrineRepositoryArrayHelper extends ArrayRepository
{
	/**
	 * @var EntityManager
	 */
	private $entityManager;

	/**
	 * @param EntityManager $em
	 */
	public function __construct(EntityManager $em)
	{
		$this->entityManager = $em;
	}

	/**
	 * Get the maximal value of interval index among the nodes
	 */
	public function getCurrentMax()
	{
		$max = 0;
		/* @var $node Node\NodeInterface */
		foreach ($this->array as $node) {
			$max = max($max, $node->getRightValue());
		}
		return $max;
	}

	/**
	 * Register the loaded node
	 * @param Node\NodeInterface $node
	 */
	public function register(Node\NodeInterface $node)
	{
		if ( ! in_array($node, $this->array, true)) {
			$this->array[] = $node;
		}
	}

	/**
	 * Free the node
	 * @param Node\NodeInterface $node
	 */
	public function free(Node\NodeInterface $node = null)
	{
		if (is_null($node)) {
			$this->array = array();
		} elseif (in_array($node, $this->array, true)) {
			$key = array_search($node,	$this->array, true);
			unset($this->array[$key]);
		}
	}

	/**
	 * Prepare the repository for removal
	 */
	public function destroy()
	{
		$this->free();
	}

	/**
	 * Additionally it will refresh the nodes from the database because index
	 * update has been made using DQL already
	 * {@inheritdoc}
	 */
	protected function moveNode(Node\NodeInterface $item, $moveLeft, $moveRight, $moveLevel)
	{
		$newLeft = $item->getLeftValue() + (int) $moveLeft;
		$newRight = $item->getRightValue() + (int) $moveRight;
		$newLevel = $item->getLevel() + (int) $moveLevel;

		$insert = $this->entityManager->getUnitOfWork()->isScheduledForInsert($item);
		
		if ($insert) {
			// Call original local move method for items not in the database yet
			parent::moveNode($item, $moveLeft, $moveRight, $moveLevel);
		} else {
			// Refresh so additional UPDATE-s are not executed on the server
			$this->entityManager->refresh($item);
		}

		// Double check the values, should not happen
		if ($item->getLeftValue() != $newLeft) {
			$item->setLeftValue($newLeft);
		}
		if ($item->getRightValue() != $newRight) {
			$item->setRightValue($newRight);
		}
		if ($item->getLevel() != $newLevel) {
			$item->setLevel($newLevel);
		}
	}
}