<?php

namespace Supra\FileStorage\Repository;

use Doctrine\ORM\EntityRepository,
		Supra\NestedSet\DoctrineRepository,
		Supra\NestedSet\RepositoryInterface,
		Doctrine\ORM\Mapping,
		Doctrine\ORM\EntityManager,
		BadMethodCallException,
		Supra\FileStorage\Entity\File;

/**
 * FileRepository
 */
class FileRepository extends EntityRepository implements RepositoryInterface
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
	 * Prepares the object to be available to garbage collector.
	 * The further work with the repository will raise errors.
	 */
	public function destroy()
	{
		$this->__call('destroy', array());
		$this->nestedSetRepository = null;
	}

	/**
	 * Get root nodes
	 * @return array
	 */
	public function getRootNodes()
	{
		$rootNodes = $this->findByLevel(0);

		return $rootNodes;
	}

}
