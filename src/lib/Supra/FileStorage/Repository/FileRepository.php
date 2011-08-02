<?php

namespace Supra\FileStorage\Repository;

use Doctrine\ORM\EntityRepository,
		Supra\NestedSet\DoctrineRepository,
		Supra\NestedSet\RepositoryInterface,
		Doctrine\ORM\Mapping,
		Doctrine\ORM\EntityManager,
		BadMethodCallException,
		Supra\FileStorage\Entity\File,
		Supra\FileStorage\Exception;

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
		$className = $class->getName();
		
		if ($className != 'Supra\FileStorage\Entity\Abstraction\File') {
			throw new Exception\LogicException("File repository should be called for file abstraction entity only, requested for '{$className}'");
		}
		
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
