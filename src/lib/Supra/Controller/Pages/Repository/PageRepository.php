<?php

namespace Supra\Controller\Pages\Repository;

use Doctrine\ORM\EntityRepository,
		Supra\NestedSet\DoctrineRepository,
		Supra\NestedSet\RepositoryInterface,
		Doctrine\ORM\Mapping,
		Doctrine\ORM\EntityManager,
		BadMethodCallException;

/**
 * Page repository
 */
class PageRepository extends EntityRepository implements RepositoryInterface
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
	 * Magic method to call methods of nested set repository class
	 * @param string $method
	 * @param array $arguments
	 * @return mixed
	 */
	function __call($method, array $arguments)
	{
		try {
			$result = parent::__call($method, $arguments);
			return $result;
		} catch (BadMethodCallException $e) {
			// Does nothing, will be attached to next exception if method does not exist
		}

		$object = $this->nestedSetRepository;
		if ( ! \method_exists($object, $method)) {
			throw new BadMethodCallException("Method $method does not exist for class " . __CLASS__ . " and it's node object.", null, $e);
		}

		$callable = array($object, $method);
		$result = \call_user_func_array($callable, $arguments);

		// Compare the result with $node and return $this on match to keep method chaining
		if ($result === $object) {
			$result = $this;
		}

		return $result;
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
	 * Prepares the object to be available to garbage collector.
	 * The further work with the repository will raise errors.
	 */
	public function destroy()
	{
		$this->__call('destroy', array());
		$this->nestedSetRepository = null;
	}
}