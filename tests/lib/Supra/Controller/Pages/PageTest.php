<?php

namespace Supra\Controller\Pages;

use Supra\Controller\Pages\Page;
use Doctrine\ORM\EntityManager;
use Supra\Database\Doctrine;

/**
 * Test class for Page.
 */
class PageTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Entity manager
	 * @var \Doctrine\ORM\EntityManager
	 */
	protected $entityManager;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		$default = Doctrine::getInstance()->getEntityManager();
		$config = $default->getConfiguration();
		$connectionOptions = array(
			'driver' => 'pdo_sqlite',
			'path' => SUPRA_DATA_PATH . 'database.test.sqlite'
		);
		$this->entityManager = EntityManager::create($connectionOptions, $config);

		$tool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
		$classNames = $this->entityManager
				->getConfiguration()
				->getMetadataDriverImpl()
				->getAllClassNames();


		$classMetadata = array();
		foreach ($classNames as $className) {
			$classMetadata[] = $this->entityManager->getClassMetadata($className);
		}
		$tool->dropSchema($classMetadata);
		$tool->createSchema($classMetadata);
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown()
	{
		$connectionOptions = $this->entityManager->getConnection()->getParams();
		$this->entityManager->close();
		$this->entityManager = null;
		/*
		if (file_exists($connectionOptions['path'])) {
			unlink($connectionOptions['path']);
		}
		 */
	}

	/**
	 * Page insertion
	 */
	public function testRootPageCreation()
	{
		$rootPage = new Page();
		$this->entityManager->persist($rootPage);
		$this->entityManager->flush();
	}

	/**
	 * 
	 */
	public function testGetId()
	{
		$this->testRootPageCreation();
		$page = $this->entityManager->find('Supra\Controller\Pages\Page', 1);
		self::assertEquals(1, $page->getId());
	}

	/**
	 * 
	 */
	public function testGetParent() {
		$this->testSetParent();
		$page = $this->entityManager->find('Supra\Controller\Pages\Page', 2);
		$parent = $page->getParent();
		self::assertEquals(1, $parent->getId());
	}

	/**
	 * 
	 */
	public function testSetParent()
	{
		$this->testRootPageCreation();
		$rootPage = $this->entityManager->find('Supra\Controller\Pages\Page', 1);

		$page = new Page();
		$this->entityManager->persist($page);
		$page->setParent($rootPage);
		$this->entityManager->flush();
	}

	/**
	 * 
	 */
	public function testGetChildren() {
		$this->testGetParent();
		$parent = $this->entityManager->find('Supra\Controller\Pages\Page', 1);
		$children = $parent->getChildren();
		self::isInstanceOf('\Doctrine\Common\Collections\ArrayCollection')->
				evaluate($children);
		self::assertEquals(1, count($children));
		self::assertEquals(2, $children[0]->getId());

		$parent = $this->entityManager->find('Supra\Controller\Pages\Page', 2);
		$children = $parent->getChildren();
		self::isInstanceOf('\Doctrine\Common\Collections\ArrayCollection')->
				evaluate($children);
		self::assertEquals(0, count($children));
	}
}