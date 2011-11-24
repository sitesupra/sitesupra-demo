<?php

namespace Supra\Tests\Doctrine;

/**
 * This test checks Doctrine problem existance that it cannot load correct metadata
 * for proxy class if it's hasn't been initialized yet
 */
class DDC1441_2Test extends \PHPUnit_Framework_TestCase
{
	const ENTITY_NAME = 'Supra\Console\Cron\Entity\CronJob';
	
	private $em;
	
	private $proxyName;
	
	protected function setUp()
	{
		parent::setUp();
		
		$this->em = \Supra\ObjectRepository\ObjectRepository::getEntityManager($this);
		$proxy = $this->em->getProxyFactory()->getProxy(self::ENTITY_NAME, -1);
		$this->proxyName = get_class($proxy);
	}
	
	public function testNotLoaded()
	{
		if (version_compare(\Doctrine\ORM\Version::VERSION, '2.1.5', 'lt')) {
			self::markTestSkipped("Is not working in Doctrine ORM " . \Doctrine\ORM\Version::VERSION);
		}
		
		// Unsets metadata
		$this->em->getMetadataFactory()->setMetadataFor($this->proxyName, null);
		
		$this->load();
	}
	
	public function testLoaded()
	{
		$this->load();
	}
	
	private function load()
	{
		$metadata = null;
		
		try {
			$metadata = $this->em->getClassMetadata($this->proxyName);
		} catch (\Doctrine\ORM\Mapping\MappingException $e) {
			self::fail("Could not load metadata for the proxy class");
		}
		
		self::assertNotEmpty($metadata);
		
		self::assertEquals(self::ENTITY_NAME, $metadata->getName());
	}
}
