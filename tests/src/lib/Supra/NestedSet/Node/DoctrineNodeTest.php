<?php

namespace Supra\Tests\NestedSet\Node;

use Supra\Tests\NestedSet\Fixture,
		Supra\NestedSet\DoctrineRepository,
		Supra\NestedSet\Node\ArrayNode,
		Supra\Controller\Pages,
		Supra\Tests\NestedSet\Model,
		Doctrine\ORM\EntityManager,
		Doctrine\ORM\Configuration,
		Supra\Database\Doctrine,
		Doctrine\ORM\Mapping\ClassMetadata;

/**
 */
class DoctrineNodeTest extends NodeTest
{
	/**
	 * @var EntityManager
	 */
	protected $entityManager;

	/**
	 * @var array
	 */
	protected $memory = array();

	/**
	 * Is database built already?
	 * @var boolean
	 */
	private static $built = false;

	/**
	 * Get Doctrine entity manager
	 * @return EntityManager
	 */
	protected function getConnection()
	{
		$em = \Supra\ObjectRepository\ObjectRepository::getEntityManager($this);
		return $em;
	}

	/**
	 * Rebuilds database schema
	 */
	public function rebuild()
	{
		if (self::$built) {
			return;
		}

		$em = $this->entityManager;
		$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
		$metaDatas = $em->getMetadataFactory()->getAllMetadata();

		// we need the product model only
		$metaDatas = \array_filter($metaDatas, function(ClassMetadata $classMetadata) {
			if ($classMetadata->namespace == 'Supra\Tests\NestedSet\Model') {
				return true;
			}
			return false;
		});

		$schemaTool->dropSchema($metaDatas);
		$schemaTool->createSchema($metaDatas);

		// Fixture data
		Fixture\DoctrineNestedSet::foodTree($this->entityManager);

		self::$built = true;
	}

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp()
	{
		\Log::debug("Setting up {$this->name}");
		$this->memory['preSetUp'] = \memory_get_usage(false);

		$em = $this->entityManager = $this->getConnection();
		$this->rebuild();

		$rep = $em->getRepository('Supra\Tests\NestedSet\Model\ProductAbstraction');
		$this->repository = $rep;

		parent::setUp();

		$this->memory['postSetUp'] = \memory_get_usage(false);

		\Log::debug("Running {$this->name}");

		$em->beginTransaction();
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown()
	{
		$this->entityManager->rollback();

		$this->memory['preTearDown'] = \memory_get_usage(false);

		// Free the entities. MUST clear entity manager after doing this
		$this->food->free();
		$this->beef->free();
		$this->yellow->free();

		$this->repository->free();
		unset($this->repository);
		unset($this->food);
		unset($this->beef);
		unset($this->yellow);

		$this->entityManager->clear();
		unset($this->entityManager);

		$this->memory['postTearDown'] = \memory_get_usage(false);
		\Log::debug("Memory usage: ", $this->memory);

		$peak = max($this->memory) - $this->memory['preSetUp'];
		$freed = max($this->memory) - $this->memory['postTearDown'];

		$peakMb = round($peak / 1024 / 1024, 2);
		if ($peak != 0) {
			$freedPercent = round($freed / $peak * 100);
		} else {
			$freedPercent = 100;
		}
		
		\Log::info("Test {$this->name} finished, used {$peakMb}Mb, freed {$freedPercent}%");
	}

	/**
	 */
	public function testSetRepository()
	{
		throw new \PHPUnit_Framework_IncompleteTestError("Not implemented");
	}

	/**
	 */
	public function testAddChild()
	{
		\Log::debug($this->repository->drawTree());

		$badBeef = new Model\Product('Bad Beef');
		$this->entityManager->persist($badBeef);

		self::assertEquals(19, $badBeef->getLeftValue());

		$this->beef->addChild($badBeef);
		
		$this->entityManager->flush();

		$output = $this->repository->drawTree();

		\Log::debug($this->repository->drawTree());

		$expected = <<<DOC
(1; 20) 0 Food
  (2; 11) 1 Fruit
    (3; 6) 2 Red
      (4; 5) 3 Cherry
    (7; 10) 2 Yellow
      (8; 9) 3 Banana
  (12; 19) 1 Meat
    (13; 16) 2 Beef
      (14; 15) 3 Bad Beef
    (17; 18) 2 Pork

DOC;

		self::assertEquals($expected, $output);
	}

	

	public function testPersistRemove()
	{
		$car = new Model\Product('Car');
		$this->entityManager->persist($car);
		self::assertEquals(19, $car->getLeftValue());
		
		$carB = new Model\Product('Car');
		$this->entityManager->persist($carB);
		self::assertEquals(21, $carB->getLeftValue());

		$this->entityManager->remove($car);
		self::assertEquals(19, $carB->getLeftValue());

		$this->entityManager->remove($carB);
		
		$this->testAddChild();
	}
	
	/**
	 * Persist the created node
	 * @param Model\Product $product
	 */
	protected function persist(Model\Product $product)
	{
		$this->entityManager->persist($product);
	}

}
