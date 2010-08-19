<?php

namespace Supra\Controller\Pages\Repository;

use Doctrine\ORM\EntityRepository,
		Supra\NestedSet\DoctrineRepository,
		Supra\NestedSet\RepositoryInterface,
		Doctrine\ORM\Mapping,
		Doctrine\ORM\EntityManager,
		BadMethodCallException;

/**
 * Template repository
 */
class TemplateRepository extends EntityRepository implements RepositoryInterface
{
	/**
	 * @var DoctrineRepository
	 */
	protected $nestedSetRepository;

	/**
	 * @param EntityManager $em
	 * @param Mapping\ClassMetadata $class
	 */
	public function __construct(EntityManager $em, Mapping\ClassMetadata $class)
	{
		parent::__construct($em, $class);
		$this->nestedSetRepository = new DoctrineRepository($em, $class);
	}

	/**
	 * @return DoctrineRepository
	 */
	public function getNestedSetRepository()
	{
		return $this->nestedSetRepository;
	}

	/**
	 * Search the product by title
	 * @param string $title
	 * @return Product
	 */
	public function byTitle($title)
	{
		$record = $this->findOneByTitle($title);
		return $record;
	}

	/**
	 * Output the dump of the whole node tree
	 * @return string
	 */
	public function drawTree()
	{
		$output = $this->nestedSetRepository->drawTree();
		return $output;
	}

	/**
	 * Free the node
	 * @param Node\NodeInterface $node
	 */
	public function free(Node\NodeInterface $node = null)
	{
		$this->nestedSetRepository->free($node);
	}

	/**
	 * Prepares the object to be available to garbage collector.
	 * The further work with the repository will raise errors.
	 */
	public function destroy()
	{
		$this->__call('destroy', array());
		$this->nestedSetRepository = null;
	}
}