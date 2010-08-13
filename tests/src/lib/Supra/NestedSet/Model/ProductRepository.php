<?php

namespace Supra\Tests\NestedSet\Model;

use Doctrine\ORM\EntityRepository,
		Supra\NestedSet\Exception,
		Supra\NestedSet\DoctrineRepository,
		Supra\NestedSet\RepositoryInterface,
		Doctrine\ORM\Mapping,
		Doctrine\ORM\EntityManager,
		BadMethodCallException;

/**
 * 
 */
class ProductRepository extends EntityRepository implements RepositoryInterface
{
	/**
	 * @var DoctrineRepository
	 */
	protected $nestedSetRepository;

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
			// do nothing, will be attached to next exception if method does not exist
		}
		
		$object = $this->getNestedSetRepository();
		if ( ! \method_exists($object, $method)) {
			throw new BadMethodCallException("Method $method does not exist for class " . __CLASS__ . " and it's node object.", null, $e);
		}

		$callable = array($object, $method);
		$result = \call_user_func_array($callable, $arguments);
		return $result;
	}

	public function byTitle($title)
	{
		$record = $this->findOneByTitle($title);
		return $record;
	}

	public function createNode($title)
	{
		$product = new Product($title);
		$this->getEntityManager()
				->persist($product);
		return $product;
	}

	public function destroy()
	{
		$this->__call('destroy', array());
		$this->nestedSetRepository = null;
	}
}