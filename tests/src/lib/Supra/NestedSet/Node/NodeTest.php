<?php

namespace Supra\Tests\NestedSet\Node;

use Supra\NestedSet\Node\NodeAbstraction;
use Supra\NestedSet\RepositoryAbstraction;
use PHPUnit_Framework_TestCase;
use Supra\Tests\NestedSet\Model;
use Supra\NestedSet\Exception\InvalidOperation;

/**
 * Description of NodeTest
 */
abstract class NodeTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var RepositoryAbstraction
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
	 * @var Model\Product
	 */
	protected $meat;
	
	/**
	 * @var Model\Product
	 */
	protected $fruit;
	
	abstract protected function delete($node);
	
	protected function setUp()
	{
		$rep = $this->repository;
		$this->food = $rep->byTitle('Food');
		$this->beef = $rep->byTitle('Beef');
		$this->yellow = $rep->byTitle('Yellow');
		$this->meat = $rep->byTitle('Meat');
		$this->fruit = $rep->byTitle('Fruit');
	}
	
	/**
	 */
	public function testSetTitle()
	{
		$this->food->setNodeTitle('Yam-yam');
		self::assertEquals('Yam-yam', $this->food->getNodeTitle());
	}

	/**
	 */
	public function testGetTitle()
	{
		self::assertEquals('Food', $this->food->getNodeTitle());
		self::assertEquals('Beef', $this->beef->getNodeTitle());
		self::assertEquals('Yellow', $this->yellow->getNodeTitle());
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
	public function testDelete()
	{
		$this->delete($this->yellow);

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

		$this->delete($this->beef);
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
		
		$this->delete($this->food);
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
		self::assertEquals('Fruit', $nodes[0]->getNodeTitle());
		self::assertEquals('Food', $nodes[1]->getNodeTitle());
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
		self::assertEquals('Banana', $nodes[0]->getNodeTitle());
		$nodes = $this->yellow->getDescendants(0, true);
		self::assertEquals('Yellow', $nodes[0]->getNodeTitle());
		self::assertEquals('Banana', $nodes[1]->getNodeTitle());
	}

	/**
	 */
	public function testGetFirstChild()
	{
		$child = $this->food->getFirstChild();
		self::assertNotNull($child);
		self::assertEquals('Fruit', $child->getNodeTitle());

		$child = $this->yellow->getFirstChild();
		self::assertNotNull($child);
		self::assertEquals('Banana', $child->getNodeTitle());
		
		$child = $this->beef->getFirstChild();
		self::assertEquals(null, $child);
	}

	/**
	 */
	public function testGetLastChild()
	{
		$child = $this->food->getLastChild();
		self::assertNotNull($child);
		self::assertEquals('Meat', $child->getNodeTitle());

		$child = $this->yellow->getLastChild();
		self::assertNotNull($child);
		self::assertEquals('Banana', $child->getNodeTitle());

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
		self::assertEquals('Pork', $this->beef->getNextSibling()->getNodeTitle());
		self::assertEquals(null, $this->yellow->getNextSibling());
	}

	/**
	 */
	public function testGetPrevSibling()
	{
		self::assertEquals(null, $this->food->getPrevSibling());
		self::assertEquals(null, $this->beef->getPrevSibling());
		self::assertNotNull($this->yellow->getPrevSibling());
		self::assertEquals('Red', $this->yellow->getPrevSibling()->getNodeTitle());
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
		self::assertEquals('Meat', $this->beef->getParent()->getNodeTitle());
		self::assertNotNull($this->yellow->getParent());
		self::assertEquals('Fruit', $this->yellow->getParent()->getNodeTitle());
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
		self::assertEquals('Fruit', $children[0]->getNodeTitle());
		self::assertEquals('Meat', $children[1]->getNodeTitle());
		
		$children = $this->yellow->getChildren();
		self::assertEquals(1, count($children));
		self::assertEquals('Banana', $children[0]->getNodeTitle());
		
		$children = $this->beef->getChildren();
		self::assertEquals(0, count($children));
	}

	/**
	 */
	public function testGetSiblings()
	{
		$siblings = $this->food->getSiblings();
		self::assertEquals(1, count($siblings));
		self::assertEquals('Food', $siblings[0]->getNodeTitle());
		
		$siblings = $this->food->getSiblings(false);
		self::assertEquals(0, count($siblings));

		$siblings = $this->yellow->getSiblings();
		self::assertEquals(2, count($siblings));
		self::assertEquals('Red', $siblings[0]->getNodeTitle());
		self::assertEquals('Yellow', $siblings[1]->getNodeTitle());
		
		$siblings = $this->yellow->getSiblings(false);
		self::assertEquals(1, count($siblings));
		self::assertEquals('Red', $siblings[0]->getNodeTitle());

		$siblings = $this->beef->getSiblings();
		self::assertEquals(2, count($siblings));
		self::assertEquals('Beef', $siblings[0]->getNodeTitle());
		self::assertEquals('Pork', $siblings[1]->getNodeTitle());

		$siblings = $this->beef->getSiblings(false);
		self::assertEquals(1, count($siblings));
		self::assertEquals('Pork', $siblings[0]->getNodeTitle());

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
	
	public function testLeafNodeAdd()
	{
		$leaf = new Model\LeafProduct("Leaf");
		$this->persist($leaf);
		$this->food->addChild($leaf);
	}
	
	/**
	 * @expectedException \Exception
	 */
	public function testLeafNodeFail()
	{
		$leaf = new Model\LeafProduct("Leaf");
		$this->persist($leaf);
		
		$leaf2 = new Model\LeafProduct("Leaf 2");
		$this->persist($leaf2);
		$leaf->addChild($leaf2);
	}
	
	abstract protected function persist(Model\Product $product);
	
	public function testNotAllowedMoveError()
	{
		$e = null;
		try {
			// Could raise exception
			$this->meat->moveAsNextSiblingOf($this->fruit);
		} catch (InvalidOperation $e) {}
		
		// Should not change structure
		$this->testDrawTree();
	}
	
	public function testNotAllowedMoveError2()
	{
		$e = null;
		try {
			// Could raise exception
			$this->meat->moveAsLastChildOf($this->food);
		} catch (InvalidOperation $e) {}
		
		// Should not change structure
		$this->testDrawTree();
	}
	
	public function testDrawTree()
	{
		$output = $this->repository->drawTree();
		
		$expected = <<<DOC
(1; 18) 0 Food
  (2; 11) 1 Fruit
    (3; 6) 2 Red
      (4; 5) 3 Cherry
    (7; 10) 2 Yellow
      (8; 9) 3 Banana
  (12; 17) 1 Meat
    (13; 14) 2 Beef
    (15; 16) 2 Pork

DOC;
		
		self::assertEquals($expected, $output);
	}
}
