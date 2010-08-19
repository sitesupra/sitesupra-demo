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
class DoctrineNodeTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var EntityManager
	 */
	protected $entityManager;

	/**
	 * @var DoctrineRepository
	 */
	protected $repository;

	/**
	 * @var Model\Product
	 */
	protected $food;

	/**
	 * @var Model\Product
	 */
	protected $beef;

	/**
	 * @var Model\Product
	 */
	protected $yellow;

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
		$supraDatabase = Doctrine::getInstance();
		$em = $supraDatabase->getEntityManager('test');
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

		$rep = $em->getRepository('Supra\Tests\NestedSet\Model\Product');
		$this->repository = $rep;

		// Get sample products to work with
		$this->food = $rep->byTitle('Food');
		$this->beef = $rep->byTitle('Beef');
		$this->yellow = $rep->byTitle('Yellow');

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
	public function testSetTitle()
	{
		$this->food->setTitle('Yam-yam');
		self::assertEquals('Yam-yam', $this->food->getTitle());
	}

	/**
	 */
	public function testGetTitle()
	{
		self::assertEquals('Food', $this->food->getTitle());
		self::assertEquals('Beef', $this->beef->getTitle());
		self::assertEquals('Yellow', $this->yellow->getTitle());
	}

	/**
	 */
	public function testSetRepository()
	{
		throw new \PHPUnit_Framework_IncompleteTestError("Not implemented");
	}

	/**
	 */
	public function testGetLeftValue()
	{
		self::assertEquals(1, $this->food->getLeftValue());
		self::assertEquals(13, $this->beef->getLeftValue());
		self::assertEquals(7, $this->yellow->getLeftValue());
	}

	/**
	 */
	public function testGetRightValue()
	{
		self::assertEquals(18, $this->food->getRightValue());
		self::assertEquals(14, $this->beef->getRightValue());
		self::assertEquals(10, $this->yellow->getRightValue());
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

	/**
	 */
	public function testDelete()
	{
		$this->yellow->delete();

		$output = $this->repository->drawTree();
		self::assertEquals(<<<DOC
(1; 14) 0 Food
  (2; 7) 1 Fruit
    (3; 6) 2 Red
      (4; 5) 3 Cherry
  (8; 13) 1 Meat
    (9; 10) 2 Beef
    (11; 12) 2 Pork

DOC
				, $output);

		$this->beef->delete();
		$output = $this->repository->drawTree();
		self::assertEquals(<<<DOC
(1; 12) 0 Food
  (2; 7) 1 Fruit
    (3; 6) 2 Red
      (4; 5) 3 Cherry
  (8; 11) 1 Meat
    (9; 10) 2 Pork

DOC
				, $output);
		
		$this->food->delete();
		$output = $this->repository->drawTree();
		self::assertEquals('', $output);
	}

	/**
	 */
	public function testGetAncestors()
	{
		self::assertEquals(array(), $this->food->getAncestors());
		self::assertEquals(1, count($this->food->getAncestors(0, true)));
		self::assertEquals(2, count($this->yellow->getAncestors()));
		self::assertEquals(1, count($this->yellow->getAncestors(1)));

		$nodes = $this->yellow->getAncestors();
		self::assertEquals('Fruit', $nodes[0]->getTitle());
		self::assertEquals('Food', $nodes[1]->getTitle());
	}

	/**
	 */
	public function testGetDescendants()
	{
		self::assertEquals(array(), $this->beef->getDescendants());
		self::assertEquals(1, count($this->beef->getDescendants(0, true)));
		self::assertEquals(8, count($this->food->getDescendants()));
		self::assertEquals(3, count($this->food->getDescendants(1, true)));

		$nodes = $this->yellow->getDescendants();
		self::assertEquals('Banana', $nodes[0]->getTitle());
		$nodes = $this->yellow->getDescendants(0, true);
		self::assertEquals('Yellow', $nodes[0]->getTitle());
		self::assertEquals('Banana', $nodes[1]->getTitle());
	}

	/**
	 */
	public function testGetFirstChild()
	{
		$child = $this->food->getFirstChild();
		self::assertNotNull($child);
		self::assertEquals('Fruit', $child->getTitle());

		$child = $this->yellow->getFirstChild();
		self::assertNotNull($child);
		self::assertEquals('Banana', $child->getTitle());
		
		$child = $this->beef->getFirstChild();
		self::assertEquals(null, $child);
	}

	/**
	 */
	public function testGetLastChild()
	{
		$child = $this->food->getLastChild();
		self::assertNotNull($child);
		self::assertEquals('Meat', $child->getTitle());

		$child = $this->yellow->getLastChild();
		self::assertNotNull($child);
		self::assertEquals('Banana', $child->getTitle());

		$child = $this->beef->getLastChild();
		self::assertEquals(null, $child);
	}

	/**
	 */
	public function testHasNextSibling()
	{
		self::assertEquals(false, $this->food->hasNextSibling());
		self::assertEquals(true, $this->beef->hasNextSibling());
		self::assertEquals(false, $this->yellow->hasNextSibling());
	}

	/**
	 */
	public function testHasPrevSibling()
	{
		self::assertEquals(false, $this->food->hasPrevSibling());
		self::assertEquals(false, $this->beef->hasPrevSibling());
		self::assertEquals(true, $this->yellow->hasPrevSibling());
	}

	/**
	 */
	public function testGetNextSibling()
	{
		self::assertEquals(null, $this->food->getNextSibling());
		self::assertNotNull($this->beef->getNextSibling());
		self::assertEquals('Pork', $this->beef->getNextSibling()->getTitle());
		self::assertEquals(null, $this->yellow->getNextSibling());
	}

	/**
	 */
	public function testGetPrevSibling()
	{
		self::assertEquals(null, $this->food->getPrevSibling());
		self::assertEquals(null, $this->beef->getPrevSibling());
		self::assertNotNull($this->yellow->getPrevSibling());
		self::assertEquals('Red', $this->yellow->getPrevSibling()->getTitle());
	}

	/**
	 */
	public function testGetNumberChildren()
	{
		self::assertEquals(2, $this->food->getNumberChildren());
		self::assertEquals(0, $this->beef->getNumberChildren());
		self::assertEquals(1, $this->yellow->getNumberChildren());
	}

	/**
	 */
	public function testGetNumberDescendants()
	{
		self::assertEquals(8, $this->food->getNumberDescendants());
		self::assertEquals(0, $this->beef->getNumberDescendants());
		self::assertEquals(1, $this->yellow->getNumberDescendants());
	}

	/**
	 */
	public function testHasParent()
	{
		self::assertEquals(false, $this->food->hasParent());
		self::assertEquals(true, $this->beef->hasParent());
		self::assertEquals(true, $this->yellow->hasParent());
	}

	/**
	 */
	public function testGetParent()
	{
		self::assertEquals(null, $this->food->getParent());
		self::assertNotNull($this->beef->getParent());
		self::assertEquals('Meat', $this->beef->getParent()->getTitle());
		self::assertNotNull($this->yellow->getParent());
		self::assertEquals('Fruit', $this->yellow->getParent()->getTitle());
	}

	/**
	 */
	public function testGetPath()
	{
		self::assertEquals('Food', $this->food->getPath());
		self::assertEquals('Food X Meat X Beef', $this->beef->getPath(' X '));
		self::assertEquals('Food > Fruit', $this->yellow->getPath(' > ', false));
	}

	/**
	 */
	public function testGetChildren()
	{
		$children = $this->food->getChildren();
		self::assertEquals(2, count($children));
		self::assertEquals('Fruit', $children[0]->getTitle());
		self::assertEquals('Meat', $children[1]->getTitle());
		
		$children = $this->yellow->getChildren();
		self::assertEquals(1, count($children));
		self::assertEquals('Banana', $children[0]->getTitle());
		
		$children = $this->beef->getChildren();
		self::assertEquals(0, count($children));
	}

	/**
	 */
	public function testGetSiblings()
	{
		$siblings = $this->food->getSiblings();
		self::assertEquals(1, count($siblings));
		self::assertEquals('Food', $siblings[0]->getTitle());
		
		$siblings = $this->food->getSiblings(false);
		self::assertEquals(0, count($siblings));

		$siblings = $this->yellow->getSiblings();
		self::assertEquals(2, count($siblings));
		self::assertEquals('Red', $siblings[0]->getTitle());
		self::assertEquals('Yellow', $siblings[1]->getTitle());
		
		$siblings = $this->yellow->getSiblings(false);
		self::assertEquals(1, count($siblings));
		self::assertEquals('Red', $siblings[0]->getTitle());

		$siblings = $this->beef->getSiblings();
		self::assertEquals(2, count($siblings));
		self::assertEquals('Beef', $siblings[0]->getTitle());
		self::assertEquals('Pork', $siblings[1]->getTitle());

		$siblings = $this->beef->getSiblings(false);
		self::assertEquals(1, count($siblings));
		self::assertEquals('Pork', $siblings[0]->getTitle());

	}

	/**
	 */
	public function testGetLevel()
	{
		self::assertEquals(0, $this->food->getLevel());
		self::assertEquals(2, $this->yellow->getLevel());
		self::assertEquals(2, $this->beef->getLevel());
	}

	/**
	 */
	public function testHasChildren()
	{
		self::assertEquals(true, $this->food->hasChildren());
		self::assertEquals(true, $this->yellow->hasChildren());
		self::assertEquals(false, $this->beef->hasChildren());
	}

	/**
	 */
	public function testMoveAsNextSiblingOf()
	{
		try {
			$this->food->moveAsNextSiblingOf($this->yellow);
			self::fail("Shoudln't be able to move parent after it's child");
		} catch (\Exception $e) {}

		$this->beef->moveAsNextSiblingOf($this->yellow);
		$output = $this->repository->drawTree();

		self::assertEquals('(1; 18) 0 Food
  (2; 13) 1 Fruit
    (3; 6) 2 Red
      (4; 5) 3 Cherry
    (7; 10) 2 Yellow
      (8; 9) 3 Banana
    (11; 12) 2 Beef
  (14; 17) 1 Meat
    (15; 16) 2 Pork
', $output);
	}

	/**
	 */
	public function testMoveAsPrevSiblingOf()
	{
		try {
			$this->food->moveAsPrevSiblingOf($this->yellow);
			self::fail("Shoudln't be able to move parent before it's child");
		} catch (\Exception $e) {}

		$this->yellow->moveAsPrevSiblingOf($this->food);
		$output = $this->repository->drawTree();

		self::assertEquals('(1; 4) 0 Yellow
  (2; 3) 1 Banana
(5; 18) 0 Food
  (6; 11) 1 Fruit
    (7; 10) 2 Red
      (8; 9) 3 Cherry
  (12; 17) 1 Meat
    (13; 14) 2 Beef
    (15; 16) 2 Pork
', $output);
	}

	/**
	 */
	public function testMoveAsFirstChildOf()
	{
		try {
			$this->food->moveAsFirstChildOf($this->yellow);
			self::fail("Shoudln't be able to move parent under it's child");
		} catch (\Exception $e) {}

		$this->yellow->moveAsFirstChildOf($this->food);
		$output = $this->repository->drawTree();

		self::assertEquals('(1; 18) 0 Food
  (2; 5) 1 Yellow
    (3; 4) 2 Banana
  (6; 11) 1 Fruit
    (7; 10) 2 Red
      (8; 9) 3 Cherry
  (12; 17) 1 Meat
    (13; 14) 2 Beef
    (15; 16) 2 Pork
', $output);
	}

	/**
	 */
	public function testMoveAsLastChildOf()
	{
		try {
			$this->food->moveAsLastChildOf($this->yellow);
			self::fail("Shoudln't be able to move parent under it's child");
		} catch (\Exception $e) {}

		$this->yellow->moveAsLastChildOf($this->food);
		$output = $this->repository->drawTree();

		self::assertEquals('(1; 18) 0 Food
  (2; 7) 1 Fruit
    (3; 6) 2 Red
      (4; 5) 3 Cherry
  (8; 13) 1 Meat
    (9; 10) 2 Beef
    (11; 12) 2 Pork
  (14; 17) 1 Yellow
    (15; 16) 2 Banana
', $output);
	}

	/**
	 */
	public function testIsLeaf()
	{
		self::assertEquals(false, $this->food->isLeaf());
		self::assertEquals(false, $this->yellow->isLeaf());
		self::assertEquals(true, $this->beef->isLeaf());
	}

	/**
	 */
	public function testIsRoot()
	{
		self::assertEquals(true, $this->food->isRoot());
		self::assertEquals(false, $this->yellow->isRoot());
		self::assertEquals(false, $this->beef->isRoot());
	}

	/**
	 */
	public function testIsAncestorOf()
	{
		self::assertEquals(true, $this->food->isAncestorOf($this->yellow));
		self::assertEquals(false, $this->beef->isAncestorOf($this->yellow));
		self::assertEquals(false, $this->beef->isAncestorOf($this->food));
		self::assertEquals(true, $this->food->isAncestorOf($this->beef));
		self::assertEquals(false, $this->food->isAncestorOf($this->food));
	}

	/**
	 */
	public function testIsDescendantOf()
	{
		self::assertEquals(false, $this->food->isDescendantOf($this->yellow));
		self::assertEquals(false, $this->beef->isDescendantOf($this->yellow));
		self::assertEquals(true, $this->beef->isDescendantOf($this->food));
		self::assertEquals(false, $this->food->isDescendantOf($this->beef));
		self::assertEquals(false, $this->food->isDescendantOf($this->food));
	}

	/**
	 */
	public function testIsEqualTo()
	{
		self::assertEquals(false, $this->food->isEqualTo($this->yellow));
		self::assertNotNull($this->yellow->getParent());
		self::assertEquals(true, $this->food->isEqualTo($this->yellow->getParent()->getParent()));
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

}
